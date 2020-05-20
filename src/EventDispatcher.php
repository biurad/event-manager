<?php /** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  EventManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/eventmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Events;

use Psr\Container\ContainerInterface;
use BiuradPHP\Events\Interfaces\EventSubscriberInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use ReflectionClass;
use ReflectionException;
use Serializable;
use SplPriorityQueue;

/**
 * The Event Dispatcher.
 *
 * This is responsible for dispatching events. Events
 * are simply aliases for class methods or functions. The Dispatcher
 * allows you to hook other functions to an event that can modify the
 * input parameters and/or the output.
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @license   BSD-3-Clause
 */
class EventDispatcher implements Interfaces\EventDispatcherInterface, Serializable, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EventListener[] */
    protected $listeners;

    /** @var ContainerInterface */
    protected $container;

    /**
     * Prototype to use when creating an event.
     *
     * @var EventListener|LazyEventListener
     */
    protected $eventPrototype;

    public function __construct()
    {
        $this->eventPrototype = function ($eventName, $target, $priority) {
            return new EventListener($eventName, $target, $priority);
        };
    }

    /**
     * Set the value of container.
     *
     * @param ContainerInterface $container
     * @return EventDispatcher
     */
    public function setContainer(ContainerInterface $container): EventDispatcher
    {
        // Just incase the container responds with a null value;
        $this->container = $container;
        $this->eventPrototype = function ($eventName, $target, $priority) use ($container) {
            return new LazyEventListener($eventName, $target, $priority, $container);
        };

        return $this;
    }

    /**
     * @param object $event An event for which to return the relevant listeners
     * @return iterable[callable] An iterable (array, iterator, or generator) of callables.  Each
     *                            callable MUST be type-compatible with $event.
     */
    public function getListenersForEvent(object $event): iterable
    {
        $queue = new SplPriorityQueue();
        foreach ($this->listeners as $eventName => $listeners) {
            if (
                is_object($event) && class_exists($eventName) &&
                get_class($event) === $eventName
            ) {
                /** @var EventListener $listener */
                foreach ($listeners as $listener) {
                    $queue->insert($listener, $listener->getPriority());
                }
            }
        }

        return $queue;
    }

    /**
     * {@inheritdoc}
     * @throws ReflectionException
     */
    public function dispatch($event, array $payload = [])
    {
        if (is_string($event) && class_exists($event)) {
            $event = $this->createListenerInstance($event, $payload);
        }

        if (is_object($event) && $this->hasListeners(get_class($event))) {
            $this->callRecursiveListeners($this->getListenersForEvent($event), $event, $payload);

            return $event;
        }

        try {
            // When the given "event" is actually an object we will assume it is an event
            // object and use the class as the event name and this event itself as the
            // payload to the handler, which makes object based events quite simple.
            $eventName = is_object($event) ? get_class($event) : $event;

            $responses = $this->callListeners($eventName, $payload, func_get_args() > 2);
        } finally {
            if (null !== $this->logger) {
                $this->logger->debug(sprintf('The "%s" event is dispatched. No listeners have been called.', $eventName));
            }
        }

        return !is_iterable($responses) ? $responses : $event;
    }

    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param string|object $event
     * @param mixed $payload
     *
     * @return mixed
     * @throws ReflectionException
     */
    public function dispatchNow($event, array $payload = [])
    {
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        return $this->dispatch($event, $payload, true);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners(string $eventName)
    {
        return !empty($this->listeners[$eventName]);
    }

    /**
     * {@inheritdoc}
     */
    public function addListener(string $eventName, $listener, int $priority = 1)
    {
        $this->listeners[$eventName][] = ($this->eventPrototype)($eventName, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener(string $eventName)
    {
        if (!$this->hasListeners($eventName)) {
            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug(sprintf('The "%s" event has been removed from event\'s stack.', $eventName));
        }

        unset($this->listeners[$eventName]);
    }

    /**
     * Get all of the listeners for a given event name.
     *
     * @param  string  $eventName
     * @return callble[]|object[]|array
     */
    public function getListeners(string $eventName)
    {
        $listeners = $this->listeners[$eventName] ?? [];
        $listeners = class_exists($eventName)
            ? $this->addInterfaceListeners($eventName, $listeners)
            : ($listeners);

        $queue = new SplPriorityQueue();
        foreach ($listeners as &$listener) {
            $queue->insert($listener, $listener->getPriority());
        }

        return $queue;
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function addSubscriber($subscriber)
    {
        if (is_bool($subscriber = $this->listenerShouldSubscribe($subscriber))) {
            return;
        }

        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (is_string($params)) {
                $this->addListener($eventName, [$subscriber, $params]);
            } elseif ((count($params) === 1 || is_int($params[1])) && is_string($params[0])) {
                $this->addListener($eventName, [$subscriber, $params[0]], isset($params[1]) && is_int($params[1]) ? $params[1] : 1);
            } else {
                foreach ($params as $listener) {
                    $listener = is_array($listener) ? $listener : [$listener];
                    $this->addListener($eventName, [$subscriber, $listener[0]], isset($listener[1]) && is_int($listener[1]) ? $listener[1] : 1);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException
     */
    public function removeSubscriber($subscriber)
    {
        if (is_bool($subscriber = $this->listenerShouldSubscribe($subscriber))) {
            return;
        }

        if (null !== $this->logger) {
            $this->logger->debug(sprintf('The subscriber "%s" has been removed from a specified event', get_class($subscriber)));
        }

        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            $this->removeListener($eventName);
        }
    }

    /**
     * Triggers the listeners of an event.
     *
     * This method can be overridden to add functionality that is executed
     * for each listener.
     *
     * @param callable[] $listeners The event listeners
     * @param object|callable|StoppableEventInterface $event The event object to pass to the event handlers/listeners\
     * @param array $arguments
     *
     * @return void
     */
    protected function callRecursiveListeners(iterable $listeners, $event, array $arguments): void
    {
        $parameters = array_merge((null !== $this->container ? [$event] : [$event, $this]), $arguments);

        /** @var EventListener $listener */
        foreach ($listeners as $listener) {
            $context = [
                'event'     => get_class($event),
                'listener'  => $listener->getEvent()
            ];

            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                if (null !== $this->logger) {
                    $this->logger->debug('Listener "[{listener}]" stopped propagation of the event "{event}".', $context);
                }
                break;
            }

            try {
                $listener($context['event'], $parameters);
            } finally {
                if (null !== $this->logger) {
                    $this->logger->debug('Notified event "{event}" to listener "[{listener}]".', $context);
                }
            }
        }
    }

    /**
     * Triggers the listeners of an event.
     *
     * @param string $eventName
     * @param array $payload
     * @param boolean|null $halt
     * 
     * @return iterable
     */
    protected function callListeners(string $eventName, array $payload, ?bool $halt): iterable
    {
        foreach ($this->getListeners($eventName) as $listener) {
            $response = $listener($eventName, $payload);

            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if (true === $halt && !is_null($response)) {
                return $response;
            }

            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if (is_bool($response) && $response !== true) {
                break;
            }

            yield $response;
        }
    }

    /**
     * Create a new instance for listener to run.
     *
     * @param string|object $class
     * @param array $arguments
     *
     * @return string
     * @throws ReflectionException
     */
    private function createListenerInstance($class, array $arguments = null)
    {
        if (is_object($class)) {
            return $class;
        }

        $instance = (new ReflectionClass($class));
        if (!$instance->isInstantiable()) {
            throw new Exceptions\EventsException("Targeted [$class] is not instantiable");
        }

        return $instance->newInstanceArgs($arguments);
    }

    /**
     * Parse the given event and payload and prepare them for dispatching.
     *
     * @param mixed $event
     * @param mixed $payload
     *
     * @return array
     */
    private function parseEventAndPayload($event, $payload)
    {
        if (is_object($event)) {
            [$payload, $event] = [[$event], $event];
        }
        $payload = $payload ?: [];

        return [$event, is_array($payload) ? $payload : [$payload]];
    }

    /**
     * Add the listeners for the event's interfaces to the given array.
     *
     * @param  string  $eventName
     * @param  array  $listeners
     * @return array
     */
    private function addInterfaceListeners($eventName, array $listeners = [])
    {
        foreach (class_implements($eventName) as $interface) {
            if (isset($this->listeners[$interface])) {
                foreach ($this->listeners[$interface] as $names) {
                    $listeners = array_merge($listeners, (array) $names);
                }
            }
        }

        return $listeners;
    }

    /**
     * Determine if the event handler class should be subcribed.
     *
     * @param EventSubscriberInterface|string $class
     *
     * @return bool|Interfaces\EventSubscriberInterface
     * @throws ReflectionException
     */
    private function listenerShouldSubscribe($class)
    {
        $arguments = [];
        if (is_string($class) && strpos($class, '@') !== false) {
            [$class, $arguments] = explode('@', $class);
            if (null !== $this->container && $this->container->has($arguments)) {
                $arguments = [$this->container->get($arguments)];
            }

            $arguments = [(is_string($arguments) && class_exists($arguments)) ? new $arguments() : $arguments];
        }

        if (($class = $this->createListenerInstance($class, $arguments)) instanceof EventSubscriberInterface) {
            return $class;
        }

        return false;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'listeners' => $this->listeners,
        ];
    }

    /**
     * @internal
     */
    final public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function __unserialize(array $data): void
    {
        $this->listeners = $data['listeners'];
    }

    /**
     * @internal
     */
    final public function unserialize($serialized)
    {
        $this->__unserialize(unserialize($serialized));
    }
}
