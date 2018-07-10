<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Task;

use function Amp\call;
use Amp\Coroutine;
use Amp\Promise;
use Amp\Success;
use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\Kernel\ApplicationContext;
use Desperado\ServiceBus\MessageBus\Configuration\MessageHandlerArgumentCollection;

/**
 *
 */
final class TaskProcessor implements Task
{
    /**
     * Message handler
     *
     * @var \Closure
     */
    private $closure;

    /**
     * @var MessageHandlerArgumentCollection
     */
    private $arguments;

    /**
     * Argument resolvers collection
     *
     * @var array<string, \Desperado\ServiceBus\MessageBus\Task\Arguments\ArgumentResolver>
     */
    private $argumentResolvers;

    /**
     * @param \Closure                         $closure
     * @param MessageHandlerArgumentCollection $arguments
     * @param array                            $argumentResolvers
     */
    public function __construct(
        \Closure $closure,
        MessageHandlerArgumentCollection $arguments,
        array $argumentResolvers
    )
    {
        $this->closure           = $closure;
        $this->arguments         = $arguments;
        $this->argumentResolvers = $argumentResolvers;
    }

    /**
     * @inheritdoc
     */
    public function __invoke(Message $message, ApplicationContext $context): Promise
    {
        $closure   = $this->closure;
        $arguments = $this->arguments;
        $resolvers = $this->argumentResolvers;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Message $message, ApplicationContext $context) use ($closure, $arguments, $resolvers): \Generator
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
     * @param MessageHandlerArgumentCollection $arguments
     * @param array                            $resolvers
     * @param Message                          $message
     * @param ApplicationContext               $context
     *
     * @return array
     */
    private static function collectArguments(
        MessageHandlerArgumentCollection $arguments,
        array $resolvers,
        Message $message,
        ApplicationContext $context
    ): array
    {
        $preparedArguments = [];

        foreach($arguments as $argument)
        {
            /** @var \Desperado\ServiceBus\MessageBus\Configuration\MessageHandlerArgument $argument */

            foreach($resolvers as $argumentResolver)
            {
                /** @var \Desperado\ServiceBus\MessageBus\Task\Arguments\ArgumentResolver $argumentResolver */

                if(true === $argumentResolver->supports($argument))
                {
                    $preparedArguments[] = $argumentResolver->resolve($message, $context, $argument);
                }
            }
        }

        return $preparedArguments;
    }
}
