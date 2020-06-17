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

namespace BiuradPHP\Events\Annotation;

use BadMethodCallException;
use Closure;

/**
 *  Annotation class for @Listener().
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Listener
{
    /** @var int */
    private $priority = 1;

    /** @var null|callable|Closure|string */
    private $event;

    public function __construct($data = null)
    {
        if (isset($data['value'])) {
            $data['event'] = (string) $data['value'];
            unset($data['value']);
        }

        foreach (!empty($data) ? $data : [] as $key => $listener) {
            if (!\property_exists($this, $key)) {
                throw new BadMethodCallException(
                    \sprintf('Unknown property "%s" on annotation "%s".', $key, \get_class($this))
                );
            }

            $this->$key = $listener;
        }
    }

    /**
     * Get the event's priority
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the event listener
     *
     * @return null|callable|Closure|string
     */
    public function getEvent()
    {
        return $this->event;
    }
}
