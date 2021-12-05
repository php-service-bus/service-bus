<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Infrastructure\Retry;

/**
 * Retry operation options.
 *
 * @psalm-immutable
 */
final class RetryOptions
{
    private const DEFAULT_RETRY_MAX_COUNT = 5;

    private const DEFAULT_RETRY_DELAY = 2000;

    /**
     * Maximum number of repetitions.
     *
     * @psalm-readonly
     * @psalm-var positive-int
     *
     * @var int
     */
    public $maxCount;

    /**
     * Delay at repetitions (milliseconds).
     *
     * @psalm-readonly
     * @psalm-var positive-int
     *
     * @var int
     */
    public $delay;

    /**
     * @psalm-param positive-int $maxCount
     * @psalm-param positive-int $delay
     */
    public function __construct(int $maxCount = self::DEFAULT_RETRY_MAX_COUNT, int $delay = self::DEFAULT_RETRY_DELAY)
    {
        $this->maxCount = $maxCount;
        $this->delay    = $delay;
    }
}
