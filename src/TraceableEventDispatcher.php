<?php

declare(strict_types=1);

/*
 * This file is part of BiuradPHP opensource projects.
 *
 * PHP version 7.2 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace BiuradPHP\Events;

use Exception;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerInterface;
use SplObjectStorage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TraceableEventDispatcher implements EventDispatcherInterface
{
    protected $logger;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var array */
    private $eventsLog = [];

    /** @var SplObjectStorage */
    private $callStack;

    /** @var array */
    private $wrappedListeners = [];

    /** @var array */
    private $orphanedEvents = [];

    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger = null)
    {
        $this->dispatcher       = $dispatcher;
        $this->logger           = $logger;
        $this->wrappedListeners = [];
        $this->orphanedEvents   = [];
    }

    /**
     * Proxies all method calls to the original event dispatcher.
     *
     * @param string $method    The method name
     * @param array  $arguments The method arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        return $this->dispatcher->{$method}(...$arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function addListener(string $eventName, $listener, int $priority = 0): void
    {
        $this->dispatcher->addListener($eventName, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->dispatcher->addSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener(string $eventName, $listener)
    {
        if (isset($this->wrappedListeners[$eventName])) {
            foreach ($this->wrappedListeners[$eventName] as $index => $wrappedListener) {
                if ($wrappedListener->getWrappedListener() === $listener) {
                    $listener = $wrappedListener;
                    unset($this->wrappedListeners[$eventName][$index]);

                    break;
                }
            }
        }

        return $this->dispatcher->removeListener($eventName, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function removeSubscriber(EventSubscriberInterface $subscriber)
    {
        return $this->dispatcher->removeSubscriber($subscriber);
    }

    /**
     * {@inheritdoc}
     */
    public function getListeners(string $eventName = null)
    {
        return $this->dispatcher->getListeners($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function getListenerPriority(string $eventName, $listener)
    {
        // we might have wrapped listeners for the event (if called while dispatching)
        // in that case get the priority by wrapper
        if (isset($this->wrappedListeners[$eventName])) {
            foreach ($this->wrappedListeners[$eventName] as $index => $wrappedListener) {
                if ($wrappedListener->getWrappedListener() === $listener) {
                    return $this->dispatcher->getListenerPriority($eventName, $wrappedListener);
                }
            }
        }

        return $this->dispatcher->getListenerPriority($eventName, $listener);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners(string $eventName = null)
    {
        return $this->dispatcher->hasListeners($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(object $event, string $eventName = null): object
    {
        $eventName = $eventName ?? \get_class($event);

        if (null === $this->callStack) {
            $this->callStack = new SplObjectStorage();
        }

        if (null !== $this->logger && $event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            $this->logger->debug(
                \sprintf('The "%s" event is already stopped. No listeners have been called.', $eventName)
            );
        }

        $timerStart = \microtime(true);
        $this->preProcess($eventName);

        try {
            $this->dispatcher->dispatch($event, $eventName);
        } finally {
            $this->postProcess($eventName);

            // Enable Profiling
            $this->setEventsLog($eventName, $timerStart);
        }

        return $event;
    }

    /**
     * @return array
     */
    public function getCalledListeners()
    {
        if (null === $this->callStack) {
            return [];
        }

        $called = [];

        foreach ($this->callStack as $listener) {
            $called[] = $listener->getInfo(\current($this->callStack->getInfo()));
        }

        return $called;
    }

    /**
     * @return array
     */
    public function getNotCalledListeners()
    {
        try {
            $allListeners = $this->getListeners();
        } catch (Exception $e) {
            if (null !== $this->logger) {
                $this->logger->info(
                    'An exception was thrown while getting the uncalled listeners.',
                    ['exception' => $e]
                );
            }

            // unable to retrieve the uncalled listeners
            return [];
        }

        $calledListeners = [];

        if (null !== $this->callStack) {
            foreach ($this->callStack as $calledListener) {
                $calledListeners[] = $calledListener->getWrappedListener();
            }
        }

        $notCalled = [];

        foreach ($allListeners as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                if (!\in_array($listener, $calledListeners, true)) {
                    if (!$listener instanceof WrappedListener) {
                        $listener = new WrappedListener($listener, null, $this);
                    }
                    $notCalled[] = $listener->getInfo($eventName);
                }
            }
        }

        \uasort($notCalled, [$this, 'sortNotCalledListeners']);

        return $notCalled;
    }

    public function getOrphanedEvents(): array
    {
        if (!$this->orphanedEvents) {
            return [];
        }

        return \array_values($this->orphanedEvents);
    }

    public function reset(): void
    {
        $this->callStack      = null;
        $this->orphanedEvents = [];
    }

    /**
     * Getter for the performance log records.
     *
     * @return array
     */
    public function getEventsLogs(): array
    {
        return $this->eventsLog;
    }

    /**
     * Setter for the performance log records.
     *
     * @param string       $eventName
     * @param float|string $timerStart
     */
    private function setEventsLog(string $eventName, $timerStart): void
    {
        $this->eventsLog[] = [
            'event'    => $eventName,
            'duration' => \number_format((\microtime(true) - $timerStart) * 1000, 2) . 'ms',
        ];
    }

    private function preProcess(string $eventName): void
    {
        if (!$this->dispatcher->hasListeners($eventName)) {
            $this->orphanedEvents[] = $eventName;

            return;
        }

        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            $priority                             = $this->getListenerPriority($eventName, $listener);
            $wrappedListener                      = new WrappedListener(
                $listener instanceof WrappedListener ? $listener->getWrappedListener() : $listener,
                null,
                $this
            );
            $this->wrappedListeners[$eventName][] = $wrappedListener;
            $this->dispatcher->removeListener($eventName, $listener);
            $this->dispatcher->addListener($eventName, $wrappedListener, $priority);
            $this->callStack->attach($wrappedListener, \compact('eventName'));
        }
    }

    private function postProcess(string $eventName): void
    {
        unset($this->wrappedListeners[$eventName]);
        $skipped = false;

        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            if (!$listener instanceof WrappedListener) { // #12845: a new listener was added during dispatch.
                continue;
            }

            // Unwrap listener
            $priority = $this->getListenerPriority($eventName, $listener);
            $this->dispatcher->removeListener($eventName, $listener);
            $this->dispatcher->addListener($eventName, $listener->getWrappedListener(), $priority);

            if (null !== $this->logger) {
                $context = ['event' => $eventName, 'listener' => $listener->getPretty()];
            }

            if ($listener->wasCalled()) {
                if (null !== $this->logger) {
                    $this->logger->debug('Notified event "{event}" to listener "{listener}".', $context);
                }
            } else {
                $this->callStack->detach($listener);
            }

            if (null !== $this->logger && $skipped) {
                $this->logger->debug('Listener "{listener}" was not called for event "{event}".', $context);
            }

            if ($listener->stoppedPropagation()) {
                if (null !== $this->logger) {
                    $this->logger->debug(
                        'Listener "{listener}" stopped propagation of the event "{event}".',
                        $context
                    );
                }

                $skipped = true;
            }
        }
    }

    private function sortNotCalledListeners(array $a, array $b)
    {
        if (0 !== $cmp = \strcmp($a['event'], $b['event'])) {
            return $cmp;
        }

        if (\is_int($a['priority']) && !\is_int($b['priority'])) {
            return 1;
        }

        if (!\is_int($a['priority']) && \is_int($b['priority'])) {
            return -1;
        }

        if ($a['priority'] === $b['priority']) {
            return 0;
        }

        if ($a['priority'] > $b['priority']) {
            return -1;
        }

        return 1;
    }
}
