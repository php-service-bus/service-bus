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
 * Scheduler operation completed
 *
 * @see EmitSchedulerOperationCommand
 */
final class SchedulerOperationEmittedEvent extends AbstractEvent
{
    /**
     * Scheduled operation identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Next operation data
     *
     * @var NextScheduledOperation|null
     */
    protected $nextOperation;

    /**
     * Get scheduled operation identifier
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
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
