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

namespace BiuradPHP\Events\Bridges;

use BiuradPHP\Events\Interfaces\EventDispatcherInterface;
use BiuradPHP\Events\TraceableEventDispatcher;
use Nette, Tracy;


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
	 */
	public function getTab(): ?string
	{

		return Nette\Utils\Helpers::capture(function () {
			require __DIR__ . '/templates/EventPanel.tab.phtml';
		});
	}


	/**
	 * Renders panel.
	 */
	public function getPanel(): string
	{
		return Nette\Utils\Helpers::capture(function () {
			$events = $this->events->getPerformanceLogs();
			require __DIR__ . '/templates/EventPanel.panel.phtml';
		});
	}
}
