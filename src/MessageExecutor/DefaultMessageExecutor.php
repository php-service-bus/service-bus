<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\MessageExecutor;

use function Amp\call;
use Amp\Promise;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use function ServiceBus\Common\throwableDetails;
use function ServiceBus\Common\throwableMessage;

/**
 *
 */
final class DefaultMessageExecutor implements MessageExecutor
{
    /** @var \Closure */
    private $closure;

    /** @var \SplObjectStorage */
    private $arguments;

    /**
     * Argument resolvers collection.
     *
     * @psalm-var array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver>
     *
     * @var \ServiceBus\ArgumentResolvers\ArgumentResolver[]
     */
    private $argumentResolvers;

    /** @var DefaultHandlerOptions */
    private $options;

    /**
     * @psalm-param array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver>  $argumentResolvers
     *
     * @param \ServiceBus\ArgumentResolvers\ArgumentResolver[] $argumentResolvers
     */
    public function __construct(
        \Closure $closure,
        \SplObjectStorage $arguments,
        DefaultHandlerOptions $options,
        array $argumentResolvers
    ) {
        $this->closure           = $closure;
        $this->arguments         = $arguments;
        $this->options           = $options;
        $this->argumentResolvers = $argumentResolvers;
    }

    /**
     * {@inheritdoc}
     */
    public function __invoke(object $message, ServiceBusContext $context): Promise
    {
        return call(
            function () use ($message, $context): \Generator
            {
                $resolvedArgs = self::collectArguments($this->arguments, $this->argumentResolvers, $message, $context);

                try
                {
                    if ($this->options->description !== null)
                    {
                        $context->logContextMessage($this->options->description);
                    }

                    yield call($this->closure, ...$resolvedArgs);
                }
                catch (\Throwable $throwable)
                {
                    if ($this->options->defaultThrowableEvent === null)
                    {
                        throw $throwable;
                    }

                    $context->logContextMessage(
                        'Error processing, sending an error event and stopping message processing',
                        throwableDetails($throwable),
                        LogLevel::ERROR
                    );

                    yield from self::publishThrowable(
                        (string) $this->options->defaultThrowableEvent,
                        throwableMessage($throwable),
                        $context
                    );
                }

                unset($resolvedArgs);
            }
        );
    }

    /**
     * Publish failed response event.
     */
    private static function publishThrowable(string $eventClass, string $errorMessage, ServiceBusContext $context): \Generator
    {
        /** @psalm-var callable(string, string):\ServiceBus\Services\Contracts\ExecutionFailedEvent $factory */
        $factory = [$eventClass, 'create'];

        $event = $factory($context->traceId(), $errorMessage);

        yield $context->delivery($event);
    }

    /**
     * Collect arguments list.
     *
     * @psalm-param  array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver> $resolvers
     * @psalm-return array<int, mixed>
     *
     * @param \ServiceBus\ArgumentResolvers\ArgumentResolver[] $resolvers
     */
    private static function collectArguments(
        \SplObjectStorage $arguments,
        array $resolvers,
        object $message,
        ServiceBusContext $context
    ): array {
        $preparedArguments = [];

        /** @var \ServiceBus\Common\MessageHandler\MessageHandlerArgument $argument */
        foreach ($arguments as $argument)
        {
            foreach ($resolvers as $argumentResolver)
            {
                if ($argumentResolver->supports($argument))
                {
                    /** @psalm-suppress MixedAssignment Unknown data type */
                    $preparedArguments[] = $argumentResolver->resolve($message, $context, $argument);
                }
            }
        }

        /** @psalm-var array<int, mixed> $preparedArguments */

        return $preparedArguments;
    }
}
