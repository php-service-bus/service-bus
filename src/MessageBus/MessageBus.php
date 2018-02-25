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
use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\ServiceBus\Task\CompletedTask;
use Psr\Log\LoggerInterface;
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
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MessageBusTaskCollection $collection
     * @param LoggerInterface          $logger
     *
     * @return self
     */
    public static function build(
        MessageBusTaskCollection $collection,
        LoggerInterface $logger
    ): self
    {
        $self = new self();

        $self->taskCollection = $collection;
        $self->logger = $logger;

        return $self;
    }

    /**
     * Handle message
     *
     * @param AbstractMessage          $message
     * @param ExecutionContextInterface $context
     *
     * @return PromiseInterface
     */
    public function handle(AbstractMessage $message, ExecutionContextInterface $context): PromiseInterface
    {
        $messageNamespace = \get_class($message);

        $promises = \array_map(
            function(MessageBusTask $messageBusTask) use ($message, $context)
            {
                return $this->executeTask($messageBusTask, $message, $context);
            },
            $this->taskCollection->mapByMessageNamespace($messageNamespace)
        );

        return all($promises);
    }

    /**
     * Process task
     *
     * @param MessageBusTask           $messageBusTask
     * @param AbstractMessage          $message
     * @param ExecutionContextInterface $context
     *
     * @return CompletedTask
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
