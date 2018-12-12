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
    public $id;

    /**
     * Reason
     *
     * @var string|null
     */
    public $reason;

    /**
     * Next operation data
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation;

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
}
