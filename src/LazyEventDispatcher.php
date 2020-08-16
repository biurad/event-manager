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

use DivineNii\Invoker\Interfaces\InvokerInterface;
use DivineNii\Invoker\Invoker;
use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * {@inheritdoc}
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class LazyEventDispatcher extends EventDispatcher
{
    /** @var InvokerInterface */
    private $resolver;

    /**
     * @param Container $container
     */
    public function __construct(InvokerInterface $invoker = null)
    {
        $this->resolver = $invoker ?? new Invoker();
    }

    /**
     * @return InvokerInterface
     */
    public function getResolver(): InvokerInterface
    {
        return $this->resolver;
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

                continue;
            }

            $this->resolver->call(
                $listener,
                [$event, $eventName, EventDispatcherInterface::class => $this]
            );
        }
    }
}
