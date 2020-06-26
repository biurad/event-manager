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
use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class WrappedListener
{
    private $listener;

    private $optimizedListener;

    private $called;

    private $stoppedPropagation;

    private $dispatcher;

    private $duration;

    private $priority;

    public function __construct($listener, EventDispatcherInterface $dispatcher = null)
    {
        $this->listener           = $listener;
        $this->optimizedListener  = $listener instanceof Closure
            ? $listener
            : (\is_callable($listener) ? Closure::fromCallable($listener) : null);

        $this->dispatcher         = $dispatcher;
        $this->called             = false;
        $this->stoppedPropagation = false;
    }

    public function __invoke(object $event, string $eventName, EventDispatcherInterface $dispatcher): void
    {
        $dispatcher = $this->dispatcher ?: $dispatcher;

        $this->called   = true;
        $this->priority = $dispatcher->getListenerPriority($eventName, $this->listener);
        $timeStart      = \microtime(true);

        if ($this->dispatcher instanceof LazyEventDispatcher) {
            BoundMethod::call(
                $this->dispatcher->getContainer(),
                ($this->optimizedListener ?? $this->listener),
                [$event, $eventName, $dispatcher]
            );
        } else {
            ($this->optimizedListener ?? $this->listener)($event, $eventName, $dispatcher);
        }

        $this->duration = \number_format((\microtime(true) - $timeStart) * 1000, 2) . 'ms';

        if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
            $this->stoppedPropagation = true;
        }
    }

    public function getWrappedListener()
    {
        return $this->listener;
    }

    public function wasCalled(): bool
    {
        return $this->called;
    }

    public function stoppedPropagation(): bool
    {
        return $this->stoppedPropagation;
    }

    public function getPretty(): string
    {
        return $this->pretty;
    }

    public function getInfo(string $eventName): array
    {
        $priority = null !== $this->priority
            ? $this->priority
            : (
                null !== $this->dispatcher
                    ? $this->dispatcher->getListenerPriority($eventName, $this->listener)
                    : null
            );

        return [
            'event'      => $eventName,
            'priority'   => $priority,
            'duration'   => $this->duration,
        ];
    }
}
