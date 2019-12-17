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

namespace BiuradPHP\Event;

use BiuradPHP\Event\Exception\EventsException;
use BiuradPHP\Event\Interfaces\SilenceInterface;

/**
 * Temporarily suppress PHP error reporting, usually warnings and below.
 *
 * Originally from Niels Keurentjes in Composer project.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 * @license MIT
 */
class Silence implements SilenceInterface
{
    /**
     * @var int[] Unpop stack
     */
    private static $stack = [];

    /**
     * Suppresses given mask or errors.
     *
     * @param int|null $mask error levels to suppress, default value NULL indicates all warnings and below
     *
     * @return int the old error reporting level
     */
    public static function suppress($mask = null)
    {
        if (!isset($mask)) {
            $mask = E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED | E_STRICT;
        }
        $old = error_reporting();
        self::$stack[] = $old;
        error_reporting($old & ~$mask);

        return $old;
    }

    /**
     * Restores a single state.
     */
    public static function restore()
    {
        if (!empty(self::$stack)) {
            error_reporting(array_pop(self::$stack));
        }
    }

    /**
     * Calls a specified function while silencing warnings and below.
     *
     * Future improvement: when PHP requirements are raised add Callable type hint (5.4) and variadic parameters (5.6)
     *
     * @param callable $callable function to execute
     *
     * @throws \Exception any exceptions from the callback are rethrown
     *
     * @return mixed return value of the callback
     */
    public static function call($callable /*, ...$parameters */)
    {
        try {
            $result = call_user_func_array($callable, array_slice(func_get_args(), 1));

            if ($result === 'eval' || $callable === 'eval') {
                throw new \BadFunctionCallException('Sorry, the function eval() has been deprecated');
            }

            return $result;
        } catch (\Exception $e) {
            // Use a finally block for this when requirements are raised to PHP 5.5
            self::restore();

            throw new EventsException($e->getMessage(), $e->getCode());
        }
    }
}
