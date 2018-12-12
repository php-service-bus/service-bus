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

use Amp\Promise;
use Kelunik\Retry\ConstantBackoff;
use function Kelunik\Retry\retry;

/**
 * A wrapper on an operation that performs repetitions in case of an error
 */
final class OperationRetryWrapper
{
    /**
     * Retry operation options
     *
     * @var RetryOptions
     */
    private $options;

    /**
     * @param RetryOptions|null $options
     */
    public function __construct(RetryOptions $options = null)
    {
        $this->options = $options ?? new RetryOptions();
    }

    /**
     * @psalm-suppress MixedTypeCoercion
     * @psalm-suppress MixedTypeCoercion
     *
     * @param callable   $operation     Wrapped operation
     * @param string ...$exceptionClasses Exceptions in which attempts are repeating the operation
     *
     * @return Promise<mixed>
     */
    public function __invoke(callable $operation, string ...$exceptionClasses): Promise
    {
        return retry(
            $this->options->maxCount,
            $operation,
            $exceptionClasses,
            new ConstantBackoff($this->options->delay)
        );
    }
}
