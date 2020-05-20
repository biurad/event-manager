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

use BiuradPHP\Support\BoundMethod;
use Closure;
use ReflectionClass;

/**
 * Event listener instance.
 *
 * Event listener definitions add the following members to what the
 * EventDispatcher accepts:
 *
 * - event: the event name to attach to.
 * - target: the targeted callback attach to.
 * - priority: the priority at which to attach the listener, if not the default.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class EventListener
{
    /**
     * Event name to which to attach.
     *
     * @var string
     */
    private $event;

    /**
     * Event target to which to attach.
     *
     * @var string|callable|object
     */
    private $target;

    /**
     * @var null|int Priority at which to attach.
     */
    private $priority;

    /**
     * @param string $eventName
     * @param callable|object|string $listener
     * @param int $priority
     */
    public function __construct(string $eventName, $listener, $priority = 1)
    {
        $this->setEvent($eventName);
        $this->setListener($listener);
        $this->setPriority($priority);
    }

    /**
     * Get the event name
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * Set the event Name
     * @param string $eventName
     */
    private function setEvent(string $eventName): void
    {
        $this->event = $eventName;
    }

    /**
     * Get the event's listener
     *
     * @return callable|string|object
     */
    public function getListener()
    {
        return $this->target;
    }

    /**
     * Set the event's listener
     *
     * @param callable|object|string $listener
     */
    private function setListener($listener): void
    {
        $this->target = $listener;
    }

    /**
     * Get the event's priority
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Set the event's priority
     *
     * @param int $priority
     */
    private function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * Use the listener as an invokable, allowing direct attachment to an EventDispatcher.
     *
     * @param string $eventName
     * @param array $parameters
     *
     * @return mixed
     */
    public function __invoke(string $eventName, array $parameters)
    {
        if (is_object($listener = $this->target) && !$listener instanceof Closure) {
            $listener = [$listener, 'invoke'];
            if (!method_exists($listener, '__invoke')) {
                return $listener;
            }
        }

        if (is_string($listener) && class_exists($listener)) {
            return (new ReflectionClass($listener))->newInstanceArgs($parameters ?: null);
        }

        return BoundMethod::call(null, $listener, $parameters);
    }
}
