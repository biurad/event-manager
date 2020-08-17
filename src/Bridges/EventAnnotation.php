<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
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

namespace Biurad\Events\Bridges;

use Biurad\Events\Annotation;
use Biurad\Events\Interfaces\EventDispatcherInterface;
use Biurad\Annotation\AnnotationLoader;
use Biurad\Annotation\Interfaces\AnnotationInterface;
use ReflectionClass;
use ReflectionMethod;

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
    private $eventAnnotationClass = Annotation\Listener::class;

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
     *
     * @param AnnotationLoader $annotation
     */
    public function register(AnnotationLoader $annotation): void
    {
        /** @var ReflectionClass $reflector */
        foreach ($annotation->findClasses($this->eventAnnotationClass) as [$reflector, ]) {
            $this->events->addSubscriber($reflector->getName());
        }

        /**
         * @var ReflectionMethod    $reflector
         * @var Annotation\Listener $methodAnnotation
         */
        foreach ($annotation->findMethods($this->eventAnnotationClass) as [$method, $methodAnnotation]) {
            $this->events->addListener(
                $methodAnnotation->getEvent(),
                [$method->class, $method->getName()],
                $methodAnnotation->getPriority()
            );
        }
    }
}
