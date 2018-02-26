<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Kernel\Events;

use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\ServiceBus\Application\EntryPoint\EntryPointContext;

/**
 * Error while executing the message
 */
final class MessageProcessingFailedEvent extends AbstractMessageFlowEvent
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
     * @param ExecutionContextInterface $executionContext
     */
    public function __construct(
        \Throwable $throwable,
        EntryPointContext $entryPointContext,
        ExecutionContextInterface $executionContext
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
    public function getThrowable(): \Throwable
    {
        return $this->throwable;
    }
}
