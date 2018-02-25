<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler;

use Desperado\Domain\DateTime;
use Desperado\Domain\Message\AbstractCommand;
use Desperado\ServiceBus\Scheduler\Identifier\ScheduledCommandIdentifier;

/**
 * Scheduled job data
 */
class ScheduledOperation
{
    /**
     * Identifier
     *
     * @var ScheduledCommandIdentifier
     */
    private $id;

    /**
     * Scheduled message
     *
     * @var AbstractCommand
     */
    private $command;

    /**
     * Execution date
     *
     * @var DateTime
     */
    private $date;

    /**
     * Is command sent
     *
     * @var bool
     */
    private $isSent = false;

    /**
     * @param ScheduledCommandIdentifier $id
     * @param AbstractCommand            $command
     * @param DateTime                   $dateTime
     *
     * @return self
     */
    public static function new(
        ScheduledCommandIdentifier $id,
        AbstractCommand $command,
        DateTime $dateTime
    ): self
    {
        $self = new self($id, $command, $dateTime);
        $self->isSent = false;

        return $self;
    }

    /**
     * Sent job
     *
     * @return self
     */
    public function sent(): self
    {
        $self = new self($this->id, $this->command, $this->date);
        $self->isSent = true;

        return $self;
    }

    /**
     * Get identifier
     *
     * @return ScheduledCommandIdentifier
     */
    public function getId(): ScheduledCommandIdentifier
    {
        return $this->id;
    }

    /**
     * Get command
     *
     * @return AbstractCommand
     */
    public function getCommand(): AbstractCommand
    {
        return $this->command;
    }

    /**
     * Get execution date
     *
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * Get sent flag
     *
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->isSent;
    }

    /**
     * @param ScheduledCommandIdentifier $id
     * @param AbstractCommand            $command
     * @param DateTime                   $dateTime
     */
    private function __construct(
        ScheduledCommandIdentifier $id,
        AbstractCommand $command,
        DateTime $dateTime
    )
    {
        $this->id = $id;
        $this->command = $command;
        $this->date = $dateTime;
    }
}
