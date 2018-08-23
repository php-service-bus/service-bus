<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Common\ExecutionContext;

use Psr\Log\LogLevel;

/**
 *
 */
interface LoggingInContext
{
    /**
     * Log message with context details
     *
     * @param string $logMessage
     * @param array  $extra
     * @param string $level
     *
     * @return void
     */
    public function logContextMessage(
        string $logMessage,
        array $extra = [],
        string $level = LogLevel::INFO
    ): void;

    /**
     * Log Throwable in execution context
     *
     * @param \Throwable $throwable
     * @param array      $extra
     * @param string     $level
     *
     * @return void
     */
    public function logContextThrowable(
        \Throwable $throwable,
        string $level = LogLevel::ERROR,
        array $extra = []
    ): void;
}
