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

namespace BiuradPHP\Events\Bridges;

use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use BiuradPHP\Events\TraceableEventDispatcher;
use Nette;
use Throwable;
use Tracy;

/**
 * Events panel for Debugger Bar.
 */
class EventsPanel implements Tracy\IBarPanel
{
    use Nette\SmartObject;

    /** @var TraceableEventDispatcher */
    private $events;

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->events = $dispatcher;
    }

    /**
     * Renders tab.
     *
     * @throws Throwable
     */
    public function getTab(): ?string
    {
        return Nette\Utils\Helpers::capture(function (): void {
            require __DIR__ . '/templates/EventPanel.tab.phtml';
        });
    }

    /**
     * Renders panel.
     *
     * @throws Throwable
     */
    public function getPanel(): string
    {
        return Nette\Utils\Helpers::capture(function (): void {
            $events = $this->events->getPerformanceLogs();

            require __DIR__ . '/templates/EventPanel.panel.phtml';
        });
    }
}
