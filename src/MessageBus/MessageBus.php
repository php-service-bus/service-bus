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
use Desperado\ServiceBus\Task\CompletedTask;
use Psr\Log\LoggerInterface;
use function React\Promise\all;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

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
     * @param AbstractExecutionContext $context
     *
     * @return PromiseInterface
     */
    public function handle(AbstractMessage $message, AbstractExecutionContext $context): PromiseInterface
    {
        $messageNamespace = \get_class($message);

        $taskCollection = $this->taskCollection->mapByMessageNamespace($messageNamespace);

        if(0 === \count($taskCollection))
        {
            $this->logger->info(
                \sprintf('No handlers found for the message with the namespace "%s', $messageNamespace)
            );

            return new FulfilledPromise();
        }

        $promises = \array_map(
            function(MessageBusTask $messageBusTask) use ($message, $context)
            {
                $task = $messageBusTask->getTask();

                return CompletedTask::create(
                    $message,
                    $context,
                    $task($message, $context, $messageBusTask->getAutowiringServices())
                );
            },
            $taskCollection
        );

        return all($promises);
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
