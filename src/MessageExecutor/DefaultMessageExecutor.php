<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\MessageExecutor;

use ServiceBus\ArgumentResolver\ChainArgumentResolver;
use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use ServiceBus\Common\MessageHandler\MessageHandlerArgument;
use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use function Amp\call;

final class DefaultMessageExecutor implements MessageExecutor
{
    /**
     * @psalm-var non-empty-string
     *
     * @var string
     */
    private $handlerHash;

    /**
     * @var \Closure
     */
    private $closure;

    /**
     * @psalm-var \SplObjectStorage<MessageHandlerArgument, null>
     *
     * @var \SplObjectStorage
     */
    private $arguments;

    /**
     * @var ChainArgumentResolver
     */
    private $argumentResolver;

    /**
     * @var DefaultHandlerOptions
     */
    private $options;

    /**
     * @psalm-param non-empty-string                                $handlerHash
     * @psalm-param \SplObjectStorage<MessageHandlerArgument, null> $arguments
     */
    public function __construct(
        string                $handlerHash,
        \Closure              $closure,
        \SplObjectStorage     $arguments,
        DefaultHandlerOptions $options,
        ChainArgumentResolver $argumentResolver
    ) {
        $this->handlerHash      = $handlerHash;
        $this->closure          = $closure;
        $this->arguments        = $arguments;
        $this->options          = $options;
        $this->argumentResolver = $argumentResolver;
    }

    public function id(): string
    {
        return $this->handlerHash;
    }

    public function retryStrategy(): ?RetryStrategy
    {
        return null;
    }

    public function __invoke(object $message, ServiceBusContext $context): Promise
    {
        return call(
            function () use ($message, $context): \Generator
            {
                $resolvedArgs = $this->argumentResolver->resolve(
                    arguments: $this->arguments,
                    message: $message,
                    context: $context
                );

                if ($this->options->description !== null)
                {
                    $context->logger()->info($this->options->description);
                }

                yield call($this->closure, ...$resolvedArgs);
            }
        );
    }
}
