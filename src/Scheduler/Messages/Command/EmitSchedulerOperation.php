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

namespace Desperado\ServiceBus\Scheduler\Messages\Command;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Fulfill the task of the scheduler
 *
 * @see SchedulerOperationEmitted
 */
final class EmitSchedulerOperation implements Command
{
    /**
     * Scheduled operation identifier
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * @param ScheduledOperationId $id
     *
     * @return self
     */
    public static function create(ScheduledOperationId $id): self
    {
        $self = new self();

        $self->id = $id;

        return $self;
    }
}
