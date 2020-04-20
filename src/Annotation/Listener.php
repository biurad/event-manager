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

namespace BiuradPHP\Events\Annotation;

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

    /** @var callable|\Closure|string|null */
    private $event;

    public function __construct($data = null)
    {
        if (isset($data['value'])) {
            $data['event'] = (string) $data['value'];
            unset($data['value']);
        }

        foreach (!empty($data) ? $data : [] as $key => $listener) {
            if (! property_exists($this, $key)) {
                throw new \BadMethodCallException(sprintf('Unknown property "%s" on annotation "%s".', $key, \get_class($this)));
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
     * @return callable|\Closure|string|null
     */
    public function getEvent()
    {
        return $this->event;
    }
}
