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

namespace BiuradPHP\Events\Interfaces;

use BiuradPHP\Events\EventListener;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * The EventDispatcherInterface is the central point of event listener system.
 * Listeners are registered on the manager and events are dispatched through the
 * manager.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
interface EventDispatcherInterface extends ListenerProviderInterface
{
    /**
     * Adds an event listener that listens on the specified events.
     *
     * @param string                 $eventName
     * @param callable|object|string $listener  The listener
     * @param int                    $priority  The higher this value, the earlier an event
     *                                          listener will be triggered in the chain (defaults to 1)
     */
    public function addListener(string $eventName, $listener, int $priority = 1);

    /**
     * Adds an event subscriber.
     *
     * The subscriber is asked for all the events it is
     * interested in and added as a listener for these events.
     *
     * NOTE: If set subscriber as string, adding a @ means on 1 important parameter
     *      should be passed onto the string eg: class@parameter.
     *
     * @param EventSubscriberInterface|string $subscriber
     */
    public function addSubscriber($subscriber);

    /**
     * Removes an event listener from the specified events.
     *
     * @param string $eventName
     */
    public function removeListener(string $eventName);

    /**
     * Remove the whole subcribed events.
     *
     * @param EventSubscriberInterface $subscriber
     */
    public function removeSubscriber($subscriber);

    /**
     * Gets the listeners of a specific event or all listeners sorted by descending priority.
     *
     * @param string $eventName
     *
     * @return iterable The event listeners for the specified event, or all event listeners by event name
     */
    public function getListener(string $eventName): iterable;

    /**
     * Get all of the listeners.
     *
     * @return array|EventListener[]
     */
    public function getListeners(): array;

    /**
     * Checks whether an event has any registered listeners.
     *
     * @param string $eventName
     *
     * @return bool true if the specified event has any listeners, false otherwise
     */
    public function hasListeners(string $eventName): bool;

    /**
     * Fire an event until the first non-null response is returned.
     *
     * @param object|string $event
     * @param mixed         $payload
     *
     * @return mixed
     */
    public function dispatchNow($event, array $payload = []);

    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object|string $event   the object to process
     * @param array         $payload The arguments passed into event
     *
     * @return object the Event that was passed,
     *                Fire an event and call the listeners
     */
    public function dispatch($event, array $payload = []);
}
