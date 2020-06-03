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

use Nette;
use Nette\Schema\Expect;
use BiuradPHP\Events\EventDispatcher;
use BiuradPHP\Events\Interfaces\EventSubscriberInterface;
use BiuradPHP\Events\TraceableEventDispatcher;

class EventsExtension extends Nette\DI\CompilerExtension
{
    /**
     * Whether or not we are in debug/development mode.
     *
     * @var bool
     */
    private $debug;

    public function __construct(bool $isDevelopmentMode = false)
    {
        $this->debug = $isDevelopmentMode;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'autoload' => Nette\Schema\Expect::bool(true),
            'subscribers' => Nette\Schema\Expect::arrayOf(Expect::string()->assert('class_exists')),
        ])->castTo('array');
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();

        $events = $builder->addDefinition($this->prefix('dispatcher'))
            ->setFactory($this->debug ? TraceableEventDispatcher::class : EventDispatcher::class)
            ->addSetup('setContainer')
            ->addSetup('setLogger')
        ;

        foreach ($this->config['subscribers'] as $subscriber) {
            $events->addSetup('addSubsciber', [$subscriber]);
        }

        $builder->addAlias('events', $this->prefix('dispatcher'));
    }

    /**
     * {@inheritdoc}
     */
    public function beforeCompile(): void
    {
        $builder = $this->getContainerBuilder();
		$dispatcher = $builder->getDefinition($this->prefix('dispatcher'));

		$subscribers = $builder->findByType(EventSubscriberInterface::class);
		foreach ($subscribers as $name => $subscriber) {
			$dispatcher->addSetup('addSubscriber', [$subscriber]);
		}
    }
}
