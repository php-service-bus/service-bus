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

namespace Desperado\ServiceBus\Infrastructure\Retry;

/**
 * Retry operation options
 */
final class RetryOptions
{
    private const DEFAULT_RETRY_MAX_COUNT = 5;
    private const DEFAULT_RETRY_DELAY     = 2000;

    /**
     * Maximum number of repetitions
     *
     * @var int
     */
    public $maxCount;

    /**
     * Delay at repetitions (milliseconds)
     *
     * @var int
     */
    public $delay;

    /**
     * @param int $maxCount
     * @param int $delay
     */
    public function __construct(int $maxCount = self::DEFAULT_RETRY_MAX_COUNT, int $delay = self::DEFAULT_RETRY_DELAY)
    {
        $this->maxCount = $maxCount;
        $this->delay    = $delay;
    }
}
