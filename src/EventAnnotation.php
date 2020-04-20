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

use BiuradPHP\Events\Interfaces\EventBroadcastInterface;
use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use BiuradPHP\Events\Interfaces\EventSubscriberInterface;
use BiuradPHP\Loader\AnnotationLocator;
use BiuradPHP\Loader\Interfaces\AnnotationInterface;

/**
 * EventAnnotation loads listeners from a PHP class or its methods.
 *
 * The @Listener annotation can be set on the class (for global parameters),
 * and on each method.
 *
 * The @Listener annotation main value is the event name.
 * The name parameter is mandatory.
 * Here is an example of how you should be able to use it:
 * ```php
 * <?php
 *  class Chat
 *  {
 *     /**
 *      * @Listener("chat.open")
 *      * /
 *     public function open()
 *     {
 *     }
 *
 *     /**
 *      * @Listener("chat.close")
 *      * /
 *     public function close(int $id)
 *     {
 *     }
 *  }
 * ```
 */
final class EventAnnotation implements AnnotationInterface
{
    /**
     * @var string<Annotation\Listener>
     */
    private $eventAnnotationClass = 'BiuradPHP\\Events\\Annotation\\Listener';

    /**
     * @var EventDispatcherInterface
     */
    private $events;

    /**
     * The RouteAnnotation Constructor.
     *
     * @param EventDispatcherInterface $events
     */
    public function __construct(EventDispatcherInterface $events)
    {
        $this->events = $events;
    }

    /**
     * Load the annoatation for events.
     */
    public function register(AnnotationLocator $annotation): void
    {
        foreach ($annotation->findClasses($this->eventAnnotationClass) as [$reflector, $classAnnotation]) {
            if ($reflector->implementsInterface(EventBroadcastInterface::class)) {
                $this->events->addListener(
                    $classAnnotation->getEvent(),
                    $reflector->getName(),
                    $classAnnotation->getPriority()
                );
            } elseif ($reflector->implementsInterface(EventSubscriberInterface::class)) {
                $this->events->addSubscriber($reflector->getName());
            }
        }

        foreach ($annotation->findMethods($this->eventAnnotationClass) as [$method, $methodAnnotation]) {
            $this->events->addListener(
                $methodAnnotation->getEvent(),
                [$method->class, $method->getName()],
                $methodAnnotation->getPriority()
            );
        }
    }
}
