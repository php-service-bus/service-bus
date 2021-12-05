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

use function Kelunik\Retry\retry;
use Amp\Promise;
use Kelunik\Retry\ConstantBackoff;

/**
 * A wrapper on an operation that performs repetitions in case of an error.
 */
final class OperationRetryWrapper
{
    /**
     * @var RetryOptions
     */
    private $options;

    public function __construct(RetryOptions $options = null)
    {
        $this->options = $options ?? new RetryOptions();
    }

    public function __invoke(callable $wrappedOperation, string ...$exceptionClasses): Promise
    {
        return retry(
            maxAttempts: $this->options->maxCount,
            actor: $wrappedOperation,
            throwable: $exceptionClasses,
            backoff: new ConstantBackoff($this->options->delay)
        );
    }
}
