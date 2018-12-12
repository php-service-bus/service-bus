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
use Desperado\ServiceBus\Common\Contract\Messages\Command;

/**
 *
 */
final class OperationScheduled implements Event
{
    /**
     * Operation identifier
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Command namespace
     *
     * @var string
     */
    public $commandNamespace;

    /**
     * Execution date
     *
     * @var \DateTimeImmutable
     */
    public $executionDate;

    /**
     * Next operation data
     *
     * @var NextScheduledOperation|null
     */
    public $nextOperation;

    /**
     * @param ScheduledOperationId        $id
     * @param Command                     $command ,
     * @param \DateTimeImmutable          $executionDate
     * @param NextScheduledOperation|null $nextOperation
     *
     * @return self
     */
    public static function create(
        ScheduledOperationId $id,
        Command $command,
        \DateTimeImmutable $executionDate,
        ?NextScheduledOperation $nextOperation
    ): self
    {
        $self = new self();

        $self->id               = $id;
        $self->commandNamespace = \get_class($command);
        $self->executionDate    = $executionDate;
        $self->nextOperation    = $nextOperation;

        return $self;
    }

    /**
     * Has next operation data
     *
     * @return bool
     */
    public function hasNextOperation(): bool
    {
        return null !== $this->nextOperation;
    }
}
