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

use InvalidArgumentException;

/**
 * Class to provide context information for a passed event.
 *
 * @author Mark Garrett <mark.garrett@allcarepharmacy.com>
 */
class EventContext
{
    /**
     * @var EventListener
     */
    protected $event;

    /**
     *
     * @var array
     */
    private $debugBacktrace = [];

    /**
     * @param EventListener $event (Optional) The event to provide context to. The event must be set either here or
     * with {@see setEvent()} before any other methods can be used.
     */
    public function __construct(EventListener $event = null)
    {
        if ($event) {
            $this->setEvent($event);
        }
    }

    /**
     * @param EventListener $event The event to add context to.
     * @return void
     */
    public function setEvent(EventListener $event): void
    {
        $this->event = $event;
    }

    /**
     * @return EventListener
     */
    public function getEvent(): EventListener
    {
        if (! $this->event) {
            throw new InvalidArgumentException(sprintf('%s: expects an event to have been set.', __METHOD__));
        }

        return $this->event;
    }

    /**
     * Returns either the class name of the target, or the target string
     *
     * @return string
     */
    public function getEventTarget()
    {
        $event = $this->getEvent();

        return $this->getEventTargetAsString($event->getListener());
    }

    /**
     * Determines a string label to represent an event target.
     *
     * @param mixed $target
     *
     * @return string
     */
    private function getEventTargetAsString($target)
    {
        if (is_object($target)) {
            return get_class($target);
        }

        if (is_resource($target)) {
            return get_resource_type($target);
        }

        if (is_scalar($target)) {
            return (string) $target;
        }

        return gettype($target);
    }

    /**
     * Returns the debug_backtrace() for this object with two levels removed so that array starts where this
     * class method was called.
     *
     * @return string
     */
    private function getDebugBacktrace()
    {
        if (! $this->debugBacktrace) {
            //Remove the levels this method introduces
            $trace = debug_backtrace();
            $this->debugBacktrace = $trace;
        }

        return $this->debugBacktrace;
    }

    /**
     * Returns the filename and parent directory of the file from which the event was triggered.
     *
     * @return string
     */
    public function getEventTriggerFile()
    {
        $backtrace = $this->getDebugBacktrace();

        if (file_exists($backtrace[4]['file'])) {
            return basename(dirname($backtrace[4]['file'])) . '/' . basename($backtrace[4]['file']);
        }

        return '';
    }

    /**
     * Returns the line number of the file from which the event was triggered.
     *
     * @return integer
     */
    public function getEventTriggerLine()
    {
        $backtrace = $this->getDebugBacktrace();

        if (isset($backtrace[4]['line'])) {
            return $backtrace[4]['line'];
        }

        return '';
    }
}
