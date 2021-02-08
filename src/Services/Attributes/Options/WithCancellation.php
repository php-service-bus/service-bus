<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Services\Attributes\Options;

/**
 * @psalm-immutable
 */
final class WithCancellation
{
    private const DEFAULT_EXECUTION_TIMEOUT = 600;

    /**
     * Operation timeout (in seconds).
     *
     * @psalm-readonly
     *
     * @var int
     */
    public $timeout;

    public static function default(): self
    {
        return new self(
            self::DEFAULT_EXECUTION_TIMEOUT
        );
    }

    public function __construct(int $timeout)
    {
        $this->timeout = $timeout;
    }
}
