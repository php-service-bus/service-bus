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

use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use function Amp\call;
use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;

/**
 *
 */
final class DefaultMessageExecutor implements MessageExecutor
{
    /**
     * @var string
     */
    private $handlerHash;

    /**
     * @var \Closure
     */
    private $closure;

    /**
     * @var \SplObjectStorage
     */
    private $arguments;

    /**
     * Argument resolvers collection.
     *
     * @psalm-var array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver>
     *
     * @var \ServiceBus\ArgumentResolvers\ArgumentResolver[]
     */
    private $argumentResolvers;

    /**
     * @var DefaultHandlerOptions
     */
    private $options;

    /**
     * @psalm-param array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver> $argumentResolvers
     *
     * @param \ServiceBus\ArgumentResolvers\ArgumentResolver[]                    $argumentResolvers
     */
    public function __construct(
        string $handlerHash,
        \Closure $closure,
        \SplObjectStorage $arguments,
        DefaultHandlerOptions $options,
        array $argumentResolvers
    ) {
        $this->handlerHash       = $handlerHash;
        $this->closure           = $closure;
        $this->arguments         = $arguments;
        $this->options           = $options;
        $this->argumentResolvers = $argumentResolvers;
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
                /** @psalm-suppress MixedArgumentTypeCoercion */
                $resolvedArgs = $this->collectArguments(
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

    /**
     * Collect arguments list.
     */
    private function collectArguments(
        \SplObjectStorage $arguments,
        object $message,
        ServiceBusContext $context
    ): array {
        $preparedArguments = [];

        /** @var \ServiceBus\Common\MessageHandler\MessageHandlerArgument $argument */
        foreach ($arguments as $argument)
        {
            foreach ($this->argumentResolvers as $argumentResolver)
            {
                if ($argumentResolver->supports($argument))
                {
                    /** @psalm-suppress MixedAssignment Unknown data type */
                    $preparedArguments[] = $argumentResolver->resolve(
                        message: $message,
                        context: $context,
                        argument: $argument
                    );
                }
            }
        }

        /** @psalm-var array<int, mixed> $preparedArguments */

        return $preparedArguments;
    }
}
