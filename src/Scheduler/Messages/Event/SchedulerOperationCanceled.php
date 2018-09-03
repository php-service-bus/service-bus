<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler\Messages\Event;

use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Scheduler operation canceled
 */
final class SchedulerOperationCanceled implements Event
{
    /**
     * Operation identifier
     *
     * @var ScheduledOperationId
     */
    private $id;

    /**
     * Reason
     *
     * @var string|null
     */
    private $reason;

    /**
     * Next operation data
     *
     * @var NextScheduledOperation|null
     */
    private $nextOperation;

    /**
     * @param ScheduledOperationId        $id
     * @param string|null                 $reason
     * @param NextScheduledOperation|null $nextScheduledOperation
     *
     * @return self
     */
    public static function create(
        ScheduledOperationId $id,
        ?string $reason,
        ?NextScheduledOperation $nextScheduledOperation
    ): self
    {
        $self                = new self();

        $self->id            = $id;
        $self->reason        = $reason;
        $self->nextOperation = $nextScheduledOperation;

        return $self;
    }

    /**
     * Receive identifier
     *
     * @return ScheduledOperationId
     */
    public function id(): ScheduledOperationId
    {
        return $this->id;
    }

    /**
     * Receive next operation data
     *
     * @return NextScheduledOperation|null
     */
    public function nextOperation(): ?NextScheduledOperation
    {
        return $this->nextOperation;
    }

    /**
     * Receive cancellation reason
     *
     * @return null|string
     */
    public function reason(): ?string
    {
        return $this->reason;
    }
}
