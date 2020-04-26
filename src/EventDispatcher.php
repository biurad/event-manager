<?php

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

use BiuradPHP\DependencyInjection\Interfaces\FactoryInterface;
use BiuradPHP\Events\Exceptions\EventsException;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Psr\Container\ContainerInterface;
use BiuradPHP\Events\Interfaces\EventBroadcastInterface;
use BiuradPHP\Events\Interfaces\EventSubscriberInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

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
class EventDispatcher implements Interfaces\EventDispatcherInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var EventListener[] */
    protected $listeners = [];

    /** @var ContainerInterface */
    protected static $container;

    /**
     * Set the value of container.
     *
     * @param \BiuradPHP\DependencyInjection\Interfaces\ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): EventDispatcher
    {
        // Just incase the container responds with a null value;
        static::$container = $container;

        return $this;
    }

    /**
     * @param object $event An event for which to return the relevant listeners
     * @return iterable[callable] An iterable (array, iterator, or generator) of callables.  Each
     *                            callable MUST be type-compatible with $event.
     */
    public function getListenersForEvent(object $event): iterable
    {
        $queue = new \SplPriorityQueue();
        foreach ($this->listeners as $eventName => $listeners) {
            if (
                is_object($event) && class_exists($eventName) &&
                get_class($event) === $eventName
            ) {
                foreach ($listeners as $listener) {
                    $queue->insert($listener->getListener(), $listener->getPriority());
                }
            }
        }

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($event, array $payload = [])
    {
        if (is_string($event) && class_exists($event)) {
            $event = $this->createListenerInstance($event, $payload);
        }

        if (is_object($event) && $this->hasListeners(get_class($event))) {
            $this->callListeners($this->getListenersForEvent($event), $event, $payload);


            return $event;
        }

        // When the given "event" is actually an object we will assume it is an event
        // object and use the class as the event name and this event itself as the
        // payload to the handler, which makes object based events quite simple.
        [$event, $payload] = $this->parseEventAndPayload($event, $payload);
        $eventName = is_object($event) ? get_class($event) : $event;

        if (null !== $this->logger) {
            $this->logger->debug(sprintf('The "%s" event is dispatched. No listeners have been called.', $eventName));
        }

        $responses = [];
        foreach ($this->getListeners($eventName) as $listener) {
            $response = $listener($eventName, $payload);

            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if (func_num_args() > 2 && !is_null($response)) {
                return $response;
            }

            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if (is_bool($response) && $response !== true) {
                break;
            }

            $responses[] = $response;
        }

        return func_num_args() > 2 ? $event : $responses;
    }

    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param string|object $event
     * @param mixed         $payload
     *
     * @return mixed
     */
    public function dispatchNow($event, array $payload = [])
    {
        return $this->dispatch($event, $payload, true);
    }

    /**
     * {@inheritdoc}
     */
    public function hasListeners(string $eventName = null)
    {
        if (null !== $eventName) {
            return !empty($this->listeners[$eventName]);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function addListener(string $eventName, $listener, int $priority = 1)
    {
        $this->listeners[$eventName][] = new EventListener($eventName, $this->makeListener($listener), $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function removeListener(string $eventName)
    {
        if (empty($this->listeners[$eventName])) {
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
     * @return \Closure[]|object[]|array
     */
    public function getListeners(string $eventName)
    {
        $listeners = $this->listeners[$eventName] ?? [];
        $listeners = class_exists($eventName)
            ? $this->addInterfaceListeners($eventName, $listeners)
            : ($listeners);

        $queue = new \SplPriorityQueue();
        foreach ($listeners as &$listener) {
            $queue->insert($listener->getListener(), $listener->getPriority());
        }

        return $queue;
    }

    /**
     * Get all of the contexts for a given event name.
     *
     * @param  string  $eventName
     * @return EventContext[]|array
     */
    public function getContexts(string $eventName): iterable
    {
        $listeners = $this->listeners[$eventName] ?? [];
        $listeners = class_exists($eventName)
            ? $this->addInterfaceListeners($eventName, $listeners)
            : ($listeners);

        foreach ($listeners as $listener) {
            yield new EventContext($listener);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addSubscriber($subscriber)
    {
        if (is_bool($subscriber = $this->listenerShouldSubscribe($subscriber))) {
            return;
        }

        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (\is_string($params)) {
                $this->addListener($eventName, [$subscriber, $params]);
            } elseif ((count($params) === 1 || is_int($params[1])) && \is_string($params[0])) {
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
     * Register an event listener with the dispatcher.
     *
     * @param \Closure|callable|object|string $listener
     *
     * @return \Closure|object|mixed
     */
    public function makeListener($listener)
    {
        if (!$listener instanceof \Closure && (is_object($listener) || $listener[0] instanceof EventSubscriberInterface)) {
            return $this->createClassCallable($listener);
        }

        return function ($event, $payload) use (&$listener) {
            if (
                (!$listener instanceof \Closure  && is_object($listener)) ||
                is_string($listener) && class_exists($listener)
            ) {
                $listener = $this->createClassCallable($listener);
            }

            return $this->resolveCallable($listener,$payload);
        };
    }

    /**
     * Resolve callables.
     *
     * @param Closure|callable|mixed $unresolved
     * @param array         $arguments
     *
     * @return Clsoure|string|null
     */
    private function resolveCallable($unresolved, $arguments)
    {
        if (null === static::$container) {
            return $unresolved(...array_values($arguments));
        } elseif (self::$container instanceof FactoryInterface) {
            return static::$container->callMethod($unresolved, $arguments);
        } elseif (self::$container instanceof ContainerContract) {
            return static::$container->call($unresolved, $arguments);
        }

        return $unresolved(...array_values($arguments));
    }

    /**
     * Check if event should be broadcasted by condition.
     *
     * @param mixed $event
     *
     * @return bool
     */
    protected function broadcastDone($event)
    {
        return method_exists($event, 'broadcastDone') ? $event->broadcastDone() : true;
    }

    /**
     * Triggers the listeners of an event.
     *
     * This method can be overridden to add functionality that is executed
     * for each listener.
     *
     * @param callable[]                     $listeners The event listeners
     * @param object|\Closure|StoppableEventInterface $event     The event object to pass to the event handlers/listeners
     */
    protected function callListeners(iterable $listeners, $event, array $arguments)
    {
        $stoppable = $event instanceof StoppableEventInterface;
        $parameters = array_merge((null !== self::$container ? [$event] : [$event, $this]), $arguments);

        /** @var callable|array $listener */
        foreach ($listeners as $listener) {
            if (null !== $this->logger) {
                $context = [
                    'event'     => get_class($event),
                    'listener'  => $listener instanceof \Closure ? 'Closure' : get_class($listener[0]),
                    'method'    => $listener instanceof \Closure ? 'Type' : $listener[1]
                ];
            }

            if ($stoppable && $event->isPropagationStopped()) {
                if (null !== $this->logger) {
                    $this->logger->debug('Listener "[{listener}::{method}]" stopped propagation of the event "{event}".', $context);
                }
                break;
            }

            try {
                if ($listener instanceof \Closure) {
                    return $listener($context['event'], $parameters);
                }

                $this->resolveCallable($listener, $parameters);
            } finally {
                if (null !== $this->logger) {
                    $this->logger->debug('Notified event "{event}" to listener "[{listener}::{method}]".', $context);
                }
            }
        }
    }

    /**
     * Create the class based event callable.
     *
     * @param string $listener
     *
     * @return callable
     */
    protected function createClassCallable($listener)
    {
        [$class, $method] = $this->parseClassCallable($listener);

        if ((is_object($class) && mb_strpos(get_class($class), 'class@anonymous') !== false) || is_object($class)) {
            $controller = $class;
        } else {
            $controller = null !== static::$container ? static::$container->get($class) : new $class();
        }

        if ($controller instanceof EventBroadcastInterface) {
            return $this->createQueuedHandlerCallable($controller);
        }

        return [$controller, $method];
    }

    /**
     * Parse the class@listener, class::method, object into class and method.
     *
     * @param string|callable|object $listener
     *
     * @return array
     */
    protected function parseClassCallable($listener)
    {
        if (is_object($listener)) {
            if (!method_exists($listener, '__invoke')) {
                throw new EventsException(sprintf('The object has to implement %s %s method, else replace with callable or string with method avialable', '__invoke'));
            }

            return [$listener, '__invoke'];
        } elseif ((is_array($listener) && count($listener) == 2) || is_callable($listener)) {
            return $listener;
        } elseif (strpos($listener, '::') !== false) {
            return explode('::', $listener, 2);
        } elseif (strpos($listener, '@') !== false) {
            return explode('@', $listener, 2);
        }
    }

    /**
     * Create a new instance for listener to run.
     *
     * @param string|object $class
     *
     * @return string
     */
    private function createListenerInstance($class, $arguments = [])
    {
        if (is_object($class)) {
            return !empty($arguments) ? get_class($arguments) : $class;
        }

        $instance = (new \ReflectionClass($class));
        if (!$instance->isInstantiable()) {
            throw new Exceptions\EventsException("Targeted [$class] is not instantiable");
        }

        if (null !== static::$container) {
            $instance = method_exists(self::$container, 'make')
                ? static::$container->make($class, ...$arguments)
                : static::$container->get($class);
        }

        return $instance instanceof \ReflectionClass ? $instance->newInstanceArgs($arguments) : $instance;
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
     */
    private function listenerShouldSubscribe($class)
    {
        $arguments = [];
        if (is_string($class) && strpos($class, '@') !== false) {
            [$class, $arguments] = explode('@', $class);

            if ((null !== self::$container && (self::$container->has($arguments) || class_exists($arguments)))) {
                $arguments = self::$container->get($arguments);
            }

            $arguments = (is_string($arguments) && class_exists($arguments)) ? new $arguments() : $arguments;
        }

        $arguments = !is_array($arguments) ? [$arguments] : $arguments;
        $class = $this->createListenerInstance($class, $arguments);

        if ($class instanceof EventSubscriberInterface) {
            return $class;
        }

        return false;
    }

    /**
     * Create a callable for putting an event handler on the queue.
     *
     * @param EventBroadcastInterface $class
     *
     * @return bool|\Closure
     */
    protected function createQueuedHandlerCallable(EventBroadcastInterface $class)
    {
        return function () use ($class): bool {
            if ($this->listenerWantsToBeQueued($class)) {
                if (null !== $this->logger) {
                    $this->logger->debug(sprintf('The "%s" broadcaster has been dispatched on request, no need to re-dispatch.', get_class($class)));
                }

                return $this->broadcastDone($class);
            }
        };
    }

    /**
     * Determine if the event handler wants to be queued.
     *
     * @param EventBroadcastInterface $class
     *
     * @return bool
     */
    protected function listenerWantsToBeQueued(EventBroadcastInterface $class)
    {
        if (method_exists($class, 'broadcastOn')) {
            $class->broadcastOn();
        }

        return true;
    }
}
