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

use Desperado\ServiceBus\Task\TaskInterface;

/**
 * Information about the task in the bus
 */
class MessageBusTask
{
    /**
     * Namespace of the message being processed
     *
     * @var string
     */
    private $messageNamespace;

    /**
     * Task
     *
     * @var TaskInterface
     */
    private $task;

    /**
     * Create task container
     *
     * @param string        $messageNamespace
     * @param TaskInterface $task
     *
     * @return MessageBusTask
     */
    public static function create(string $messageNamespace, TaskInterface $task): self
    {
        $self = new self();

        $self->messageNamespace = $messageNamespace;
        $self->task = $task;

        return $self;
    }

    /**
     * Get message namespace
     *
     * @return string
     */
    public function getMessageNamespace(): string
    {
        return $this->messageNamespace;
    }

    /**
     * Get task
     *
     * @return TaskInterface
     */
    public function getTask(): TaskInterface
    {
        return $this->task;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
