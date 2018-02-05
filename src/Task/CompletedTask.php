<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Task;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * Completed task
 */
class CompletedTask
{
    /**
     * Message
     *
     * @var AbstractMessage
     */
    private $message;

    /**
     * Context
     *
     * @var AbstractExecutionContext
     */
    private $context;

    /**
     * Operation result
     *
     * @var PromiseInterface
     */
    private $taskResult;

    /**
     * @param AbstractMessage          $message
     * @param AbstractExecutionContext $context
     * @param PromiseInterface|null    $taskResult
     *
     * @return CompletedTask
     */
    public static function create(AbstractMessage $message, AbstractExecutionContext $context, ?PromiseInterface $taskResult = null): self
    {
        $self = new self();

        $self->message = $message;
        $self->context = $context;
        $self->taskResult = $taskResult ?? new FulfilledPromise();

        return $self;
    }

    /**
     * Get message
     *
     * @return AbstractMessage
     */
    public function getMessage(): AbstractMessage
    {
        return $this->message;
    }

    /**
     * Get execution context
     *
     * @return AbstractExecutionContext
     */
    public function getContext(): AbstractExecutionContext
    {
        return $this->context;
    }

    /**
     * Get task execution result
     *
     * @return PromiseInterface
     */
    public function getTaskResult(): PromiseInterface
    {
        return $this->taskResult;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
