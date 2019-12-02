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
use function ServiceBus\Common\collectThrowableDetails;
use Amp\Promise;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageExecutor\MessageExecutor;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;

/**
 *
 */
final class DefaultMessageExecutor implements MessageExecutor
{
    /**
     * Message handler.
     *
     * @var \Closure
     */
    private $closure;

    /**
     * @psalm-var \SplObjectStorage<\ServiceBus\Common\MessageHandler\MessageHandlerArgument, string>
     *
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
     * Execution options.
     *
     * @var DefaultHandlerOptions
     */
    private $options;

    /**
     * @psalm-param \SplObjectStorage<\ServiceBus\Common\MessageHandler\MessageHandlerArgument, string> $arguments
     * @psalm-param array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver>
     *              $argumentResolvers
     *
     * @param \Closure                                         $closure
     * @param \SplObjectStorage                                $arguments
     * @param DefaultHandlerOptions                            $options
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
        $argumentResolvers = $this->argumentResolvers;

        return call(
            static function(
                \Closure $closure,
                \SplObjectStorage $arguments,
                DefaultHandlerOptions $options,
                object $message,
                ServiceBusContext $context
            ) use ($argumentResolvers): \Generator
            {
                try
                {
                    /**
                     * @psalm-var \SplObjectStorage<\ServiceBus\Common\MessageHandler\MessageHandlerArgument, string> $arguments
                     * @psalm-var array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver> $argumentResolvers
                     */
                    $resolvedArgs = self::collectArguments($arguments, $argumentResolvers, $message, $context);

                    yield call($closure, ...$resolvedArgs);

                    unset($resolvedArgs);
                }
                catch (\Throwable $throwable)
                {
                    if (null === $options->defaultThrowableEvent)
                    {
                        throw $throwable;
                    }

                    $context->logContextMessage(
                        'Error processing, sending an error event and stopping message processing',
                        collectThrowableDetails($throwable),
                        LogLevel::DEBUG
                    );

                    yield from self::publishThrowable(
                        (string) $options->defaultThrowableEvent,
                        $throwable->getMessage(),
                        $context
                    );
                }
            },
            $this->closure,
            $this->arguments,
            $this->options,
            $message,
            $context
        );
    }

    /**
     * Publish failed response event.
     *
     * @param string            $eventClass
     * @param string            $errorMessage
     * @param ServiceBusContext $context
     *
     * @return \Generator
     */
    private static function publishThrowable(string $eventClass, string $errorMessage, ServiceBusContext $context): \Generator
    {
        /**
         * @noinspection VariableFunctionsUsageInspection
         *
         * @var \ServiceBus\Services\Contracts\ExecutionFailedEvent $event
         */
        $event = \forward_static_call_array([$eventClass, 'create'], [$context->traceId(), $errorMessage]);

        yield $context->delivery($event);
    }

    /**
     * Collect arguments list.
     *
     * @psalm-param \SplObjectStorage<\ServiceBus\Common\MessageHandler\MessageHandlerArgument, string> $arguments
     * @psalm-param  array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver> $resolvers
     * @psalm-return array<int, mixed>
     *
     * @param \SplObjectStorage                                $arguments
     * @param \ServiceBus\ArgumentResolvers\ArgumentResolver[] $resolvers
     * @param object                                           $message
     * @param ServiceBusContext                                $context
     *
     * @return array
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
                if (true === $argumentResolver->supports($argument))
                {
                    /** @psalm-suppress MixedAssignment Unknown data type */
                    $preparedArguments[] = $argumentResolver->resolve($message, $context, $argument);
                }
            }
        }

        /** @var array<int, mixed> $preparedArguments */

        return $preparedArguments;
    }
}
