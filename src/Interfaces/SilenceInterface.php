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

namespace BiuradPHP\Event\Interfaces;

use BiuradPHP\Event\Exceptions\EventsException;

interface SilenceInterface
{
    /**
     * Suppresses given mask or errors.
     *
     * @param int|null $mask error levels to suppress, default value NULL indicates all warnings and below
     *
     * @return int the old error reporting level
     */
    public static function suppress($mask = null);

    /**
     * Restores a single state.
     *
     * @return mixed
     */
    public static function restore();

    /**
     * Calls a specified function while silencing warnings and below.
     *
     * Future improvement: when PHP requirements are raised add Callable type hint (5.4) and variadic parameters (5.6)
     *
     * @param callable $callable function to execute
     *
     * @throws EventsException any exceptions from the callback are rethrown
     *
     * @return mixed return value of the callback
     */
    public static function call($callable /*, ...$parameters */);
}
