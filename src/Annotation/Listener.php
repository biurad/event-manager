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

namespace Biurad\Events\Annotation;

use BadMethodCallException;

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

    /** @var null|string */
    private $event;

    /**
     * @param array<string,mixed> $data
     */
    public function __construct(array $data = null)
    {
        if (isset($data['value'])) {
            $data['event'] = (string) $data['value'];
            unset($data['value']);
        }

        foreach ($data ?? [] as $key => $listener) {
            if (!\property_exists($this, $key)) {
                throw new BadMethodCallException(
                    \sprintf('Unknown property "%s" on annotation "%s".', $key, __CLASS__)
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
     * @return null|string
     */
    public function getEvent(): ?string
    {
        return $this->event;
    }
}
