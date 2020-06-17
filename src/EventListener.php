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

use BiuradPHP\Support\BoundMethod;
use Closure;
use ReflectionClass;
use ReflectionException;

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
     * @var callable|object|string
     */
    private $target;

    /**
     * @var null|int priority at which to attach
     */
    private $priority;

    /**
     * @param string                 $eventName
     * @param callable|object|string $listener
     * @param int                    $priority
     */
    public function __construct(string $eventName, $listener, $priority = 1)
    {
        $this->setEvent($eventName);
        $this->setListener($listener);
        $this->setPriority($priority);
    }

    /**
     * Use the listener as an invokable, allowing direct attachment to an EventDispatcher.
     *
     * @param string $eventName
     * @param array  $parameters
     *
     * @throws ReflectionException
     * @return mixed
     */
    public function __invoke(string $eventName, array $parameters)
    {
        if (\is_object($listener = $this->target) && !$listener instanceof Closure) {
            $listener = [$listener, 'invoke'];

            if (!\method_exists($listener, '__invoke')) {
                return $listener;
            }
        }

        if (\is_string($listener) && \class_exists($listener)) {
            return (new ReflectionClass($listener))->newInstanceArgs($parameters ?: null);
        }

        return BoundMethod::call(null, $listener, $parameters);
    }

    /**
     * Get the event name
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * Get the event's listener
     *
     * @return callable|object|string
     */
    public function getListener()
    {
        return $this->target;
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
     * Set the event Name
     * @param string $eventName
     */
    private function setEvent(string $eventName): void
    {
        $this->event = $eventName;
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
     * Set the event's priority
     *
     * @param int $priority
     */
    private function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }
}
