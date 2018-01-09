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
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Psr\Log\LoggerInterface;

/**
 * Message bus
 */
class MessageBus
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
     * @return MessageBus
     */
    public static function build(MessageBusTaskCollection $collection, LoggerInterface $logger): self
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
     * @param AbstractExecutionContext $context
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function handle(AbstractMessage $message, AbstractExecutionContext $context): void
    {
        $messageNamespace = \get_class($message);

        $taskCollection = $this->taskCollection->mapByMessageNamespace($messageNamespace);

        if(0 === \count($taskCollection))
        {
            $this->logger->notice(
                \sprintf('No handlers found for the message with the namespace "%s', $messageNamespace)
            );

            return;
        }

        foreach($taskCollection as $task)
        {
            $task = $task->getTask();

            $task($message, $context);
        }
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
