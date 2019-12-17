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

namespace BiuradPHP\Event;

use BiuradPHP\Event\Interfaces\EventInterface;
use BiuradPHP\Event\Exceptions\EventsException;
use BiuradPHP\Event\Interfaces\BroadcastInterface;
use BiuradPHP\DependencyInjection\Interfaces\FactoryInterface;

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
class Dispatcher implements EventInterface
{
    protected $listeners = [];

    /** @var FactoryInterface */
    protected static $container;

    /**
     * The Events Constructor.
     *
     * @param FactoryInterface|null $container
     */
    public function __construct(FactoryInterface $container = null)
    {
        static::$container = $container;
    }

    /**
     * @param Closure $callback
     *
     * @return static
     */
    public static function create(Closure $callback = null)
    {
        // Check if $callback is instance of Closure we return callback
        if (null !== $callback && $callback instanceof Closure) {
            return $callback(new static(static::$container));
        }

        // Return instance of the Event Handler
        return new static(static::$container);
    }

    /**
     * Override all callbacks for a given event with a new callback.
     *
     * @param string         $event
     * @param callable|mixed $callback
     */
    public function override($event, $callback)
    {
        $this->forget($event);

        return $this->listen($event, $callback);
    }

    /**
     * Get the value of events.
     */
    public function getEvents()
    {
        return $this->listeners;
    }

    /**
     * Clears an event. If no name is given,
     * all events are removed.
     *
     * @param string $event
     *
     * @return mixed|void
     */
    public function flush($event = null)
    {
        if (null !== $event) {
            unset($this->listeners[$event]);
        } else {
            unset($this->listeners);
        }
    }

    /**
     * Invokes a method.
     *
     * @param mixed $func   Class method
     * @param array $params Class method parameters
     *
     * @return mixed Function results
     */
    public static function invokeMethod($func, $params = [])
    {
        if (null === static::$container) {
            return $func(...$params);
        }

        return static::$container->callMethod($func, $params);
    }

    /**
     * Resolve callables.
     *
     * @param Closure|mixed $unresolved
     * @param array         $arguments
     *
     * @return Clsoure|string|null
     */
    private function resolveCallable($unresolved, &$arguments)
    {
        if (!is_callable($unresolved)) {
            return new \InvalidArgumentException('Callable must be callable, '.gettype($unresolved).' given');
        }

        if (is_callable($unresolved) && null !== static::$container) {
            return static::$container->callMethod($unresolved, $arguments);
        }

        if ($unresolved instanceof Closure) {
            return $unresolved->call($this, $arguments);
        }

        return $unresolved(...$arguments);
    }

    /**
     * Set the value of container.
     *
     * @param \BiuradPHP\DependencyInjection\Interfaces\FactoryInterface $container
     */
    public function setContainer(FactoryInterface $container): Dispatcher
    {
        // Just incase the container responds with a null value;
        static::$container = $container;

        return $this;
    }

    /**
     * Register an event listener with the dispatcher.
     *
     * @param string|array $events
     * @param mixed        $listener
     */
    public function listen($events, $listener)
    {
        foreach ((array) $events as $event) {
            $this->listeners[$event][] = $this->makeListener($listener);
        }

        return $this;
    }

    /**
     * Determine if a given event has listeners.
     *
     * @param string $eventName
     *
     * @return bool
     */
    public function hasListeners($eventName)
    {
        return isset($this->listeners[$eventName]);
    }

    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param string|object $event
     * @param mixed         $payload
     *
     * @return array|null
     */
    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload, true);
    }

    /**
     * Fire an event and call the listeners.
     *
     * @param string|object $event
     * @param mixed         $payload
     * @param bool          $halt
     *
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        if ('*' === $event || !is_string($event) || empty($event)) {
            throw new \InvalidArgumentException(sprintf(
                'Event name passed to %s must be a non-empty, non-wildcard string',
                __METHOD__
            ));
        }

        // When the given "event" is actually an object we will assume it is an event
        // object and use the class as the event name and this event itself as the
        // payload to the handler, which makes object based events quite simple.
        [$event, $payload] = $this->parseEventAndPayload($event, $payload);

        $responses = [];

        foreach ($this->getListeners($event) as $listener) {
            $response = $listener($event, $payload);

            // If a response is returned from the listener and event halting is enabled
            // we will just return this response, and not call the rest of the event
            // listeners. Otherwise we will add the response on the response list.
            if ($halt && !is_null($response)) {
                return $response;
            }

            // If a boolean false is returned from a listener, we will stop propagating
            // the event to any further listeners down in the chain, else we keep on
            // looping through the listeners and firing every one in our sequence.
            if ($response === false) {
                break;
            }

            $responses[] = $response;
        }

        return $halt ? null : $responses;
    }

    /**
     * Parse the given event and payload and prepare them for dispatching.
     *
     * @param mixed $event
     * @param mixed $payload
     *
     * @return array
     */
    protected function parseEventAndPayload($event, $payload)
    {
        if (is_object($event)) {
            [$payload, $event] = [[$event], get_class($event)];
        }
        $payload = $payload ?: [];

        return [$event, is_array($payload) ? $payload : [$payload]];
    }

    /**
     * Determine if the payload has a broadcastable event.
     *
     * @param string|object $payload
     *
     * @return bool
     */
    public function shouldBroadcast($payload)
    {
        return isset($payload) &&
               $payload instanceof BroadcastInterface;
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
        return method_exists($event, 'broadcastDone')
                ? $event->broadcastDone() : true;
    }

    /**
     * Get all of the listeners for a given event name.
     *
     * @param string $eventName
     *
     * @return array
     */
    public function getListeners($eventName)
    {
        $listeners = $this->listeners[$eventName] ?? [];

        return class_exists($eventName, false)
                    ? $this->addInterfaceListeners($eventName, $listeners)
                    : $listeners;

        throw new EventsException(
            'Event listener definition is missing a valid "listener" member; cannot dispatch listener'
        );
    }

    /**
     * Add the listeners for the event's interfaces to the given array.
     *
     * @param string $eventName
     * @param array  $listeners
     *
     * @return array
     */
    protected function addInterfaceListeners($eventName, array $listeners = [])
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
     * Register an event listener with the dispatcher.
     *
     * @param \Closure|string $listener
     *
     * @return \Closure
     */
    public function makeListener($listener)
    {
        if (is_string($listener)) {
            return $this->createClassListener($listener);
        }

        return function ($event, $payload) use ($listener) {
            return $listener(...array_values($payload));
        };
    }

    /**
     * Create a class based listener using the IoC container.
     *
     * @param string $listener
     *
     * @return \Closure
     */
    public function createClassListener($listener)
    {
        return function ($event, $payload) use ($listener) {
            return $this->resolveCallable(
                $this->createClassCallable($listener), $payload
            );
        };
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

        if ($this->handlerShouldBeQueued($class)) {
            return $this->createQueuedHandlerCallable($class);
        }

        if (null !== static::$container) {
            $controller = static::$container->make($class);
        }

        return [$controller ?: new $class(), $method];
    }

    /**
     * Parse the class listener into class and method.
     *
     * @param string $listener
     *
     * @return array
     */
    protected function parseClassCallable($listener)
    {
        return mb_strpos($listener, '@') ?
            explode('@', $listener, 2) : [$listener, 'handle'];
    }

    /**
     * Determine if the event handler class should be queued.
     *
     * @param string $class
     *
     * @return bool
     */
    protected function handlerShouldBeQueued($class)
    {
        try {
            return (new \ReflectionClass($class))->implementsInterface(
                BroadcastInterface::class
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Create a callable for putting an event handler on the queue.
     *
     * @param string $class
     *
     * @return \Closure
     */
    protected function createQueuedHandlerCallable($class)
    {
        return function () use ($class) {
            if ($this->handlerWantsToBeQueued($class)) {
                return $this->queueHandler($class);
            }
        };
    }

    /**
     * Determine if the event handler wants to be queued.
     *
     * @param string $class
     *
     * @return bool
     */
    protected function handlerWantsToBeQueued($class)
    {
        $instance = $this->createListenerInstance($class);

        if (method_exists($instance, 'broadcastOn')) {
            $instance->broadcastOn();
        }

        return true;
    }

    /**
     * Queue the handler class.
     *
     * @param string $class
     */
    protected function queueHandler($class)
    {
        $instance = $this->createListenerInstance($class);

        if ($this->shouldBroadcast($instance)) {
            return $this->broadcastDone($instance);
        }
    }

    /**
     * Create a new instance for listener to run.
     *
     * @param string $class
     *
     * @return string
     */
    protected function createListenerInstance($class)
    {
        $instance = (new \ReflectionClass($class))->newInstanceWithoutConstructor();

        if (null !== static::$container) {
            $instance = static::$container->make($class);
        }

        return $instance;
    }

    /**
     * Remove a set of listeners from the dispatcher.
     *
     * @param string $event
     */
    public function forget($event)
    {
        unset($this->listeners[$event]);
    }
}
