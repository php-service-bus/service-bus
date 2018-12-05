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
     * @param \Closure                                                                $closure
     * @param HandlerArgumentCollection                                               $arguments
     * @param array<string, \Desperado\ServiceBus\ArgumentResolvers\ArgumentResolver> $argumentResolvers
     */
    public function __construct(\Closure $closure, HandlerArgumentCollection $arguments, array $argumentResolvers)
    {
        $this->closure           = $closure;
        $this->arguments         = $arguments;
        $this->argumentResolvers = $argumentResolvers;
    }

    /**
     * @inheritDoc
     */
    public function __invoke(Message $message, KernelContext $context): Promise
    {
        /** @psalm-suppress MixedArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            $this->closure,
            ...self::collectArguments($this->arguments, $this->argumentResolvers, $message, $context)
        );
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
