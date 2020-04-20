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

namespace BiuradPHP\Events;

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
     */
    public function dispatch($event, array $payload = [])
    {
        $timerStart = microtime(true);

        try {
            return parent::dispatch($event, $payload);
        } catch (\Exception $e) {
            if (null !== $this->logger) {
                $this->logger->info('An exception was thrown while getting the uncalled listeners.', ['exception' => $e]);
            }

            throw $e;
        } finally {
            // Enable Profiling
            static::setPerformanceLogs(is_object($event) ? get_class($event) : $event, $timerStart);
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
     * @param string $eventName
     * @param string|float $timerStart
     */
    public static function setPerformanceLogs(string $eventName, $timerStart): void
    {
        static::$performanceLog[] = [
            'event' => $eventName,
            'duration' => number_format((microtime(true) - $timerStart) * 1000, 2) . 'ms'
        ];
    }
}
