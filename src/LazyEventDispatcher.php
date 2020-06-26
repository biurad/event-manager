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
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * {@inheritdoc}
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class LazyEventDispatcher extends EventDispatcher
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * {@inheritdoc}
     */
    protected function callListeners(iterable $listeners, string $eventName, object $event): void
    {
        $stoppable = $event instanceof StoppableEventInterface;

        foreach ($listeners as $listener) {
            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }

            if ($listener instanceof WrappedListener) {
                $listener($event, $eventName, $this);
            } else {
                BoundMethod::call($this->container, $listener, [$event, $eventName, $this]);
            }
        }
    }
}
