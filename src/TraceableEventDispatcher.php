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

namespace BiuradPHP\Events;

use Exception;

class TraceableEventDispatcher extends EventDispatcher
{
    /**
     * Stores information about the events
     * for display in the profiling.
     *
     * @var array
     */
    protected static $performanceLog = [];

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function dispatch($event, array $payload = [])
    {
        $timerStart = \microtime(true);

        try {
            return parent::dispatch($event, $payload);
        } catch (Exception $e) {
            if (null !== $this->logger) {
                $this->logger->info(
                    'An exception was thrown while getting the uncalled listeners.',
                    ['exception' => $e]
                );
            }

            throw $e;
        } finally {
            // Enable Profiling
            static::setPerformanceLogs(\is_object($event) ? \get_class($event) : $event, $timerStart);
        }
    }

    /**
     * Getter for the performance log records.
     *
     * @return array
     */
    public static function getPerformanceLogs(): array
    {
        return static::$performanceLog;
    }

    /**
     * Setter for the performance log records.
     *
     * @param string       $eventName
     * @param float|string $timerStart
     */
    public static function setPerformanceLogs(string $eventName, $timerStart): void
    {
        static::$performanceLog[] = [
            'event'    => $eventName,
            'duration' => \number_format((\microtime(true) - $timerStart) * 1000, 2) . 'ms',
        ];
    }
}
