<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Kernel\ApplicationContext;
use Desperado\ServiceBus\MessageBus\Exceptions\NoMessageHandlersFound;
use Desperado\ServiceBus\MessageBus\Task\TaskProcessor;
use Desperado\ServiceBus\MessageBus\Task\TaskMap;

/**
 *
 */
final class MessageBus
{
    /**
     * @var TaskMap
     */
    private $taskMap;

    /**
     * @param TaskMap $taskMap
     */
    public function __construct(TaskMap $taskMap)
    {
        $this->taskMap = $taskMap;
    }

    /**
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     *
     * @param ApplicationContext $context
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NoMessageHandlersFound
     */
    public function dispatch(ApplicationContext $context): Promise
    {
        $taskMap = $this->taskMap;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ApplicationContext $context) use ($taskMap): \Generator
            {
                $message      = $context->incomingEnvelope()->denormalized();
                $messageClass = \get_class($message);

                if(false === $taskMap->hasTask(\get_class($message)))
                {
                    throw new NoMessageHandlersFound($message);
                }

                foreach($taskMap->map($messageClass) as $task)
                {
                    /** @var TaskProcessor $task */

                    yield $task($message, $context);
                }
            },
            $context
        );
    }
}
