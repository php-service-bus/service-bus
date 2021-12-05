<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\MessageExecutor;

use Amp\CancellationToken;
use Amp\Deferred;
use Amp\NullCancellationToken;
use Amp\Promise;
use Amp\TimeoutCancellationToken;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use function Amp\asyncCall;
use function Amp\call;

final class TimeLimitedExecutor implements MessageExecutor
{
    /**
     * @var MessageExecutor
     */
    private $executor;

    /**
     * @var DefaultHandlerOptions
     */
    private $options;

    /**
     * @var non-empty-string|null
     */
    private $cancellationWatcher;

    public function __construct(MessageExecutor $executor, DefaultHandlerOptions $options)
    {
        $this->executor = $executor;
        $this->options  = $options;
    }

    public function id(): string
    {
        return $this->executor->id();
    }

    public function retryStrategy(): ?RetryStrategy
    {
        return $this->executor->retryStrategy();
    }

    public function __invoke(object $message, ServiceBusContext $context): Promise
    {
        $timeStart = \microtime(true);
        $deferred  = new Deferred();

        $cancellationToken = $this->createCancellationToken();

        /** @psalm-var non-empty-string $cancellationWatcher */
        $cancellationWatcher = $cancellationToken->subscribe(
            function () use ($message, $context, $cancellationToken, $deferred)
            {
                try
                {
                    $cancellationToken->throwIfRequested();
                }
                catch (\Throwable $throwable)
                {
                    $context->logger()->error(
                        '`{incomingMessage}` message processing canceled by timeout (`{timeout}` seconds)',
                        [
                            'incomingMessage' => \get_class($message),
                            'timeout'         => $this->options->executionTimeout
                        ]
                    );

                    $deferred->fail($throwable);
                }
            }
        );

        $this->cancellationWatcher = $cancellationWatcher;

        asyncCall(
            function () use ($cancellationToken, $deferred, $timeStart, $message, $context)
            {
                try
                {
                    yield call($this->executor, $message, $context);

                    $deferred->resolve();
                }
                catch (\Throwable $throwable)
                {
                    $deferred->resolve($throwable);
                }
                finally
                {
                    if ($this->cancellationWatcher !== null)
                    {
                        $cancellationToken->unsubscribe($this->cancellationWatcher);
                    }

                    $context->logger()->debug(
                        'The processing of the `{incomingMessage}` message is complete. Elapsed time: `{executionTime}`',
                        [
                            'incomingMessage' => \get_class($message),
                            'executionTime'   => \sprintf('%.5f', (string) (\microtime(true) - $timeStart))
                        ]
                    );
                }
            }
        );

        /**
         * @noinspection OneTimeUseVariablesInspection PhpUnnecessaryLocalVariableInspection
         * @psalm-var Promise<void> $promise
         */
        $promise = $deferred->promise();

        return $promise;
    }

    private function createCancellationToken(): CancellationToken
    {
        $timeout = $this->options->executionTimeout;

        return $timeout !== null
            ? new TimeoutCancellationToken(timeout: $timeout * 1000)
            : new NullCancellationToken();
    }
}
