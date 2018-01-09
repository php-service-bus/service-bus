<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\KernelEvents;

use Desperado\ServiceBus\EntryPoint\EntryPointContext;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;

/**
 * Error while executing the message
 */
class MessageProcessingFailedEvent extends AbstractMessageFlowEvent
{
    public const EVENT_NAME = 'service_bus.kernel_events.failed_execution';

    /**
     * Exception
     *
     * @var \Throwable
     */
    private $throwable;

    /**
     * @param \Throwable               $throwable
     * @param EntryPointContext        $entryPointContext
     * @param AbstractExecutionContext $executionContext
     */
    public function __construct(
        \Throwable $throwable,
        EntryPointContext $entryPointContext,
        AbstractExecutionContext $executionContext
    )
    {
        parent::__construct($entryPointContext, $executionContext);

        $this->throwable = $throwable;
    }

    /**
     * Get exception
     *
     * @return \Throwable
     */
    final public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }
}
