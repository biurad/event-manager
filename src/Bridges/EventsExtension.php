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

use BiuradPHP\Events\Interfaces\EventSubscriberInterface;
use BiuradPHP\Events\LazyEventDispatcher;
use BiuradPHP\Events\TraceableEventDispatcher;
use Nette;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use Nette\Schema\Expect;
use Psr\Log\LoggerInterface;

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
            'autoload'    => Nette\Schema\Expect::bool(true),
            'subscribers' => Nette\Schema\Expect::arrayOf(Expect::string()->assert('class_exists')),
        ])->castTo('array');
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        $events  = new Statement(LazyEventDispatcher::class);

        $logger = $builder->getByType(LoggerInterface::class) ? new Reference(LoggerInterface::class) : null;

        $events = $builder->addDefinition($this->prefix('dispatcher'))
            ->setFactory($this->debug ? new Statement(TraceableEventDispatcher::class, [$events, $logger]) : $events);

        foreach ($this->config['subscribers'] as $subscriber) {
            if (\is_string($subscriber) && $builder->hasDefinition($subscriber)) {
                $subscriber = new Reference($subscriber);
            }

            $events->addSetup('addSubscriber', [\is_string($subscriber) ? new Statement($subscriber) : $subscriber]);
        }

        $builder->addAlias('events', $this->prefix('dispatcher'));
    }

    /**
     * {@inheritdoc}
     */
    public function beforeCompile(): void
    {
        $builder    = $this->getContainerBuilder();
        $dispatcher = $builder->getDefinition($this->prefix('dispatcher'));

        $subscribers = $builder->findByType(EventSubscriberInterface::class);

        foreach ($subscribers as $name => $subscriber) {
            $subscriberDefinition = $builder->getDefinition($name);
            $builder->removeDefinition($name);

            $dispatcher->addSetup('addSubscriber', [$subscriberDefinition->getFactory()]);
        }
    }
}
