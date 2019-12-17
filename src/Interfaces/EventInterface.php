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

namespace BiuradPHP\Event\Interfaces;

interface EventInterface
{
    /**
     * Register an event listener with the dispatcher.
     *
     * @param string|array $events
     * @param mixed        $listener
     */
    public function listen($events, $listener);

    /**
     * Determine if a given event has listeners.
     *
     * @param string $eventName
     *
     * @return bool
     */
    public function hasListeners($eventName);

    /**
     * Fire an event and call the listeners.
     *
     * @param string|object $event
     * @param mixed         $payload
     * @param bool          $halt
     *
     * @return array|null
     */
    public function dispatch($event, $payload = [], $halt = false);

    /**
     * Get all of the listeners for a given event name.
     *
     * @param string $eventName
     *
     * @return array
     */
    public function getListeners($eventName);

    /**
     * Register an event listener with the dispatcher.
     *
     * @param \Closure|string $listener
     *
     * @return \Closure
     */
    public function makeListener($listener);

    /**
     * Invokes a method.
     *
     * @param mixed $func   Class method
     * @param array $params Class method parameters
     *
     * @return mixed Function results
     */
    public static function invokeMethod($func, $params = []);

    /**
     * Determine if the payload has a broadcastable event.
     *
     * @param string|object $payload
     *
     * @return bool
     */
    public function shouldBroadcast($payload);
}
