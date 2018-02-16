<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler\Events;

use Desperado\Domain\Message\AbstractEvent;
use Desperado\ServiceBus\Scheduler\NextScheduledOperation;

/**
 *
 */
final class OperationScheduledEvent extends AbstractEvent
{
    /**
     * Operation identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Command namespace
     *
     * @var string
     */
    protected $commandNamespace;

    /**
     * Execution date
     *
     * @var string
     */
    protected $executionDate;

    /**
     * Next operation data
     *
     * @var NextScheduledOperation|null
     */
    protected $nextOperation;

    /**
     * Get identifier
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get command namespace
     *
     * @return string
     */
    public function getCommandNamespace(): string
    {
        return $this->commandNamespace;
    }

    /**
     * Get execution date
     *
     * @return string
     */
    public function getExecutionDate(): string
    {
        return $this->executionDate;
    }

    /**
     * Get next operation data
     *
     * @return NextScheduledOperation|null
     */
    public function getNextOperation(): ?NextScheduledOperation
    {
        return $this->nextOperation;
    }
}
