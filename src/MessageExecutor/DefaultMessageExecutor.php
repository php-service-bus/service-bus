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

namespace Desperado\ServiceBus\MessageExecutor;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\MessageHandlers\HandlerArgumentCollection;
use Desperado\ServiceBus\MessageHandlers\HandlerOptions;
use Psr\Log\LogLevel;

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
     * @var HandlerArgumentCollection
     */
    private $arguments;

    /**
     * Argument resolvers collection
     *
     * @var array<string, \Desperado\ServiceBus\ArgumentResolvers\ArgumentResolver>
     */
    private $argumentResolvers;

    /**
     * Execution options
     *
     * @var HandlerOptions
     */
    private $options;

    /**
     * @param \Closure                                                                $closure
     * @param HandlerArgumentCollection                                               $arguments
     * @param HandlerOptions                                                          $options
     * @param array<string, \Desperado\ServiceBus\ArgumentResolvers\ArgumentResolver> $argumentResolvers
     */
    public function __construct(
        \Closure $closure,
        HandlerArgumentCollection $arguments,
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
                \Closure $closure, HandlerArgumentCollection $arguments, HandlerOptions $options, Message $message,
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
        /** @var \Desperado\ServiceBus\Services\Contracts\ExecutionFailedEvent $event */
        $event = \forward_static_call_array([$eventClass, 'create'], [$context->traceId(), $errorMessage]);

        yield $context->delivery($event);
    }

    /**
     * Collect arguments list
     *
     * @param HandlerArgumentCollection $arguments
     * @param array                     $resolvers
     * @param Message                   $message
     * @param KernelContext             $context
     *
     * @return array<int, mixed>
     */
    private static function collectArguments(
        HandlerArgumentCollection $arguments,
        array $resolvers,
        Message $message,
        KernelContext $context
    ): array
    {
        /** @var array<int, mixed> $preparedArguments */
        $preparedArguments = [];

        /** @var \Desperado\ServiceBus\MessageHandlers\HandlerArgument $argument */
        foreach($arguments as $argument)
        {
            /** @var \Desperado\ServiceBus\ArgumentResolvers\ArgumentResolver $argumentResolver */
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
