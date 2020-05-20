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

use BiuradPHP\Events\Exceptions\EventsException;
use BiuradPHP\Support\BoundMethod;
use Closure;
use Nette\DI\Container;
use Psr\Container\ContainerInterface;

/**
 * Lazy listener instance.
 *
 * Used as an internal class for the EventDispatcher to allow lazy creation of
 * listeners via a dependency injection container.
 *
 * Lazy event listener definitions add the following members to what the
 * EventDispatcher accepts:
 *
 * - event: the event name to attach to.
 * - target: the targeted callback attach to.
 * - priority: the priority at which to attach the listener, if not the default.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class LazyEventListener extends EventListener
{
    /**
     * Container from which to pull listener and resolve.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param string $eventName
     * @param callable|object|string $listener
     * @param int $priority
     * @param ContainerInterface $container
     */
    public function __construct(string $eventName, $listener, int $priority, ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct($eventName, $listener, $priority);
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(string $eventName, array $parameters)
    {
        if (is_object($listener = $this->getListener()) && !$listener instanceof Closure) {
            $listener = [$listener, 'invoke'];
            if (!method_exists($listener, '__invoke')) {
                return $listener;
            }
        }

        if ($this->container->has($eventName)) {
            throw new EventsException(
                sprintf('Lazy listener name "%s" cannot exist in in dependency injection container', $eventName)
            );
        }

        // Return the listener found in any type of container.
        if (is_string($listener) && $this->container->has($listener)) {
            // For Laminas Container, we at ease
            if (method_exists($this->container, 'build')) {
                return $this->container->build($listener, $parameters ?: null);
            }

            return $this->container->get($listener);
        }

        // For Nette Container, we at ease...
        if (
            $this->container instanceof Container &&
            is_string($listener) && class_exists($listener)
        ) {
            return $this->container->createInstance($listener, $parameters);
        }

        return BoundMethod::call($this->container, $listener, $parameters);
    }
}
