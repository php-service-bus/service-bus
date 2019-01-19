<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
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
use ServiceBus\Common\Messages\Message;
use ServiceBus\Context\KernelContext;
use ServiceBus\MessageHandlers\HandlerOptions;

/**
 *
 */
final class DefaultMessageExecutor implements MessageExecutor
{
    /**
     * Message handler
     *
     * @var \Closure
     */
    private $closure;

    /**
     * @var \SplObjectStorage<\ServiceBus\MessageHandlers\HandlerArgument>
     */
    private $arguments;

    /**
     * Argument resolvers collection
     *
     * @var array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver>
     */
    private $argumentResolvers;

    /**
     * Execution options
     *
     * @var HandlerOptions
     */
    private $options;

    /**
     * @param \Closure                                                                 $closure
     * @param \SplObjectStorage<\ServiceBus\MessageHandlers\HandlerArgument> $arguments
     * @param HandlerOptions                                                           $options
     * @param array<string, \ServiceBus\ArgumentResolvers\ArgumentResolver>  $argumentResolvers
     */
    public function __construct(
        \Closure $closure,
        \SplObjectStorage $arguments,
        HandlerOptions $options,
        array $argumentResolvers
    )
    {
        $this->closure           = $closure;
        $this->arguments         = $arguments;
        $this->options           = $options;
        $this->argumentResolvers = $argumentResolvers;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(Message $message, KernelContext $context): Promise
    {
        $argumentResolvers = $this->argumentResolvers;

        /**
         * @psalm-suppress  MixedArgument Incorrect psalm unpack parameters (...$args)
         * @psalm-suppress  InvalidArgument Incorrect psalm unpack parameters (...$args)
         */
        return call(
            static function(
                \Closure $closure, \SplObjectStorage $arguments, HandlerOptions $options, Message $message,
                KernelContext $context
            ) use ($argumentResolvers): \Generator
            {
                try
                {
                    $resolvedArgs = self::collectArguments($arguments, $argumentResolvers, $message, $context);

                    /** @psalm-suppress MixedArgument Incorrect psalm unpack parameters (...$args) */
                    yield call($closure, ...$resolvedArgs);

                    unset($resolvedArgs);
                }
                catch(\Throwable $throwable)
                {
                    if(false === $options->hasDefaultThrowableEvent())
                    {
                        throw $throwable;
                    }

                    $context->logContextMessage(
                        'Error processing, sending an error event and stopping message processing', [
                        'eventClass'       => $options->defaultThrowableEvent(),
                        'throwableMessage' => $throwable->getMessage(),
                        'throwablePoint'   => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                    ],
                        LogLevel::DEBUG
                    );

                    yield from self::publishThrowable(
                        (string) $options->defaultThrowableEvent(),
                        $throwable->getMessage(),
                        $context
                    );
                }
            },
            $this->closure, $this->arguments, $this->options, $message, $context
        );
    }

    /**
     * Publish failed response event
     *
     * @param string        $eventClass
     * @param string        $errorMessage
     * @param KernelContext $context
     *
     * @return \Generator
     */
    private static function publishThrowable(string $eventClass, string $errorMessage, KernelContext $context): \Generator
    {
        /**
         * @noinspection VariableFunctionsUsageInspection
         * @var \ServiceBus\Services\Contracts\ExecutionFailedEvent $event
         */
        $event = \forward_static_call_array([$eventClass, 'create'], [$context->traceId(), $errorMessage]);

        yield $context->delivery($event);
    }

    /**
     * Collect arguments list
     *
     * @param \SplObjectStorage $arguments
     * @param array             $resolvers
     * @param Message           $message
     * @param KernelContext     $context
     *
     * @return array<int, mixed>
     */
    private static function collectArguments(
        \SplObjectStorage $arguments,
        array $resolvers,
        Message $message,
        KernelContext $context
    ): array
    {
        /** @var array<int, mixed> $preparedArguments */
        $preparedArguments = [];

        /** @var \ServiceBus\MessageHandlers\HandlerArgument $argument */
        foreach($arguments as $argument)
        {
            /** @var \ServiceBus\ArgumentResolvers\ArgumentResolver $argumentResolver */
            foreach($resolvers as $argumentResolver)
            {
                if(true === $argumentResolver->supports($argument))
                {
                    /** @psalm-suppress MixedAssignment Unknown data type */
                    $preparedArguments[] = $argumentResolver->resolve($message, $context, $argument);
                }
            }
        }

        return $preparedArguments;
    }
}
