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

use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\Application\Context\ExecutionContextInterface;
use Desperado\ServiceBus\Task\CompletedTask;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use React\Promise\RejectedPromise;

/**
 * Message bus
 */
final class MessageBus
{
    /**
     * Tasks
     *
     * @var MessageBusTaskCollection
     */
    private $taskCollection;

    /**
     * @param MessageBusTaskCollection $collection
     *
     * @return self
     */
    public static function build(MessageBusTaskCollection $collection): self
    {
        $self = new self();

        $self->taskCollection = $collection;

        return $self;
    }

    /**
     * Handle message
     *
     * @param AbstractMessage           $message
     * @param ExecutionContextInterface $context
     *
     * @return PromiseInterface
     *
     * @throws \InvalidArgumentException
     */
    public function handle(AbstractMessage $message, ExecutionContextInterface $context): PromiseInterface
    {
        $promises = \array_map(
            function(MessageBusTask $messageBusTask) use ($message, $context)
            {
                return $this->executeTask($messageBusTask, $message, $context);
            },
            $this->taskCollection->mapByMessageNamespace($message->getMessageClass())
        );

        return all($promises);
    }

    /**
     * Process task
     *
     * @param MessageBusTask            $messageBusTask
     * @param AbstractMessage           $message
     * @param ExecutionContextInterface $context
     *
     * @return CompletedTask
     *
     * @throws \InvalidArgumentException
     */
    private function executeTask(
        MessageBusTask $messageBusTask,
        AbstractMessage $message,
        ExecutionContextInterface $context
    ): CompletedTask
    {
        $task = $messageBusTask->getTask();

        try
        {
            $result = $task($message, $context, $messageBusTask->getAutowiringServices());
        }
        catch(\Throwable $throwable)
        {
            $result = new RejectedPromise($throwable);
        }

        return CompletedTask::create(
            $message,
            $context,
            $result
        );
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
