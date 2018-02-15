<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler\Contract\Event;

use Desperado\Domain\Message\AbstractEvent;
use Desperado\ServiceBus\Scheduler\NextScheduledOperation;

/**
 * Scheduler operation canceled
 *
 * @see CancelSchedulerOperationCommand
 */
final class SchedulerOperationCanceledEvent extends AbstractEvent
{
    /**
     * Scheduled operation identifier
     *
     * @var string
     */
    protected $id;

    /**
     * Reason for canceling the scheduler job
     *
     * @var string|null
     */
    protected $reason;

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
     * Get reason for canceling the scheduler job
     *
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
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
