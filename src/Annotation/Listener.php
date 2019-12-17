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

namespace BiuradPHP\Event\Annotation;

/**
 *  Annotation class for @Listener().
 *
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Listener
{
    /**
     * @var string
     */
    public $priority;

    public function __construct($value = null)
    {
        if (isset($value['value']) && is_string($value['value'])) {
            $this->priority = (string) $value['value'];
        }
    }

    /**
     * Get the value of priority
     *
     * @return  string
     */
    public function getPriority()
    {
        return $this->priority;
    }
}
