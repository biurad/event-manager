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

use Nette, BiuradPHP;
use Nette\Schema\Expect;

class EventsExtension extends Nette\DI\CompilerExtension
{
    /**
     * {@inheritDoc}
     */
    public function getConfigSchema(): Nette\Schema\Schema
    {
        return Nette\Schema\Expect::structure([
            'subscribers' => Nette\Schema\Expect::arrayOf(Expect::string()->assert('class_exists')),
        ])->castTo('array');
    }

    /**
     * {@inheritDoc}
     */
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        $builder->addDefinition($this->prefix('dispatcher'))
            ->setFactory(BiuradPHP\Events\EventDispatcher::class)
            ->addSetup('setContainer')
            ->addSetup('setLogger')
            ->addSetup(
            'foreach (? as $subscriber) { ?->addSubscriber($subscriber); }', [$this->config['subscribers'], '@self']
        );

        $builder->addAlias('events', $this->prefix('dispatcher'));
    }
}
