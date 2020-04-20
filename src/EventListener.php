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

final class EventListener
{
    private $event;
    private $listener;
    private $priority;

    /**
     * @param string $eventName
     * @param callable|\Closure|string $listener
     * @param integer $priority
     */
    public function __construct(string $eventName, $listener, int $priority = 1)
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
     */
    private function setEvent(string $eventName): void
    {
        $this->event = $eventName;
    }

    /**
     * Get the event's listener
     *
     * @return object|\Closure
     */
    public function getListener()
    {
        return $this->listener;
    }

    /**
     * Set the event's listener
     *
     * @param callable|\Closure|string $listener
     */
    private function setListener($listener): void
    {
        $this->listener = $listener;
    }

    /**
     * Get the event's priority
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Set the event's priority
     */
    private function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }
}
