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

namespace Desperado\ServiceBus\Scheduler\Data;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Scheduled job data
 */
final class ScheduledOperation
{
    /**
     * Identifier
     *
     * @var ScheduledOperationId
     */
    private $id;

    /**
     * Scheduled message
     *
     * @var Command
     */
    private $command;

    /**
     * Execution date
     *
     * @var \DateTimeImmutable
     */
    private $date;

    /**
     * Is command sent
     *
     * @var bool
     */
    private $isSent = false;

    /**
     * @param ScheduledOperationId $id
     * @param Command              $command
     * @param \DateTimeImmutable   $dateTime
     */
    public function __construct(ScheduledOperationId $id, Command $command, \DateTimeImmutable $dateTime)
    {
        $this->id      = $id;
        $this->command = $command;
        $this->date    = $dateTime;
    }

    /**
     * Sent job
     *
     * @return self
     */
    public function sent(): self
    {
        $self         = new self($this->id, $this->command, $this->date);
        $self->isSent = true;

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
     * Receive command
     *
     * @return Command
     */
    public function command(): Command
    {
        return $this->command;
    }

    /**
     * Receive execution date
     *
     * @return \DateTimeImmutable
     */
    public function date(): \DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * Is command sent
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->isSent;
    }
}
