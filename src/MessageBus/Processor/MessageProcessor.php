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

namespace Desperado\ServiceBus\MessageBus\Processor;

use function Amp\call;
use Amp\Coroutine;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\MessageBus\MessageHandler\HandlerArgumentCollection;

/**
 * Command\event processor
 */
final class MessageProcessor implements Processor
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
     * @var array<string, \Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ArgumentResolver>
     */
    private $argumentResolvers;

    /**
     * @param \Closure                  $closure
     * @param HandlerArgumentCollection $arguments
     * @param array                     $argumentResolvers
     */
    public function __construct(\Closure $closure, HandlerArgumentCollection $arguments, array $argumentResolvers)
    {
        $this->closure           = $closure;
        $this->arguments         = $arguments;
        $this->argumentResolvers = $argumentResolvers;
    }


    /**
     * @inheritdoc
     */
    public function __invoke(Message $message, KernelContext $context): Promise
    {
        $closure   = $this->closure;
        $arguments = $this->arguments;
        $resolvers = $this->argumentResolvers;

        /** @psalm-suppress InvalidArgument */
        return call(
            static function(Message $message, KernelContext $context) use ($closure, $arguments, $resolvers): \Generator
            {
                return yield self::adapt(
                    $closure(...self::collectArguments($arguments, $resolvers, $message, $context))
                );
            },
            $message, $context
        );
    }

    /**
     * Adapt result
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @param mixed $result
     *
     * @return Promise<null>
     */
    private static function adapt($result): Promise
    {
        if($result instanceof \Generator)
        {
            return new Coroutine($result);
        }

        if($result instanceof Promise)
        {
            return $result;
        }

        return new Success();
    }

    /**
     * Collect arguments list
     *
     * @param HandlerArgumentCollection $arguments
     * @param array                     $resolvers
     * @param Message                   $message
     * @param KernelContext             $context
     *
     * @return array
     */
    private static function collectArguments(
        HandlerArgumentCollection $arguments,
        array $resolvers,
        Message $message,
        KernelContext $context
    ): array
    {
        $preparedArguments = [];

        foreach($arguments as $argument)
        {
            /** @var \Desperado\ServiceBus\MessageBus\MessageHandler\HandlerArgument $argument */

            foreach($resolvers as $argumentResolver)
            {
                /** @var \Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ArgumentResolver $argumentResolver */

                if(true === $argumentResolver->supports($argument))
                {
                    $preparedArguments[] = $argumentResolver->resolve($message, $context, $argument);
                }
            }
        }

        return $preparedArguments;
    }
}
