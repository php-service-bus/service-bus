<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Handlers\Exceptions;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;

/**
 * Unfulfilled promise
 */
class UnfulfilledPromiseData
{
    /**
     * Message
     *
     * @var AbstractMessage
     */
    private $message;

    /**
     * Intercepted exception
     *
     * @var \Throwable
     */
    private $throwable;

    /**
     * Execution context
     *
     * @var AbstractExecutionContext
     */
    private $context;

    /**
     * @param \Throwable               $throwable
     * @param AbstractMessage          $message
     * @param AbstractExecutionContext $context
     *
     * @return UnfulfilledPromiseData
     */
    public static function create(\Throwable $throwable, AbstractMessage $message, AbstractExecutionContext $context): self
    {
        $self = new self();

        $self->throwable = $throwable;
        $self->message = $message;
        $self->context = $context;

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
     * Get throwable
     *
     * @return \Throwable
     */
    public function getThrowable(): \Throwable
    {
        return $this->throwable;
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
     * Close constructor
     */
    private function __construct()
    {

    }
}
