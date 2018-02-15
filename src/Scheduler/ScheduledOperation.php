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
use Desperado\Domain\ParameterBag;
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
     * Headers
     *
     * @var ParameterBag
     */
    private $headersBag;

    /**
     * @param ScheduledCommandIdentifier $id
     * @param AbstractCommand            $command
     * @param DateTime                   $dateTime
     * @param ParameterBag|null          $headersBag
     *
     * @return ScheduledOperation
     */
    public static function new(
        ScheduledCommandIdentifier $id,
        AbstractCommand $command,
        DateTime $dateTime,
        ParameterBag $headersBag = null
    ): self
    {
        $self = new self($id, $command, $dateTime, $headersBag ?? new ParameterBag());
        $self->isSent = false;

        return $self;
    }

    /**
     * Sent job
     *
     * @return ScheduledOperation
     */
    public function sent(): self
    {
        $self = new self($this->id, $this->command, $this->date, $this->headersBag);
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
     * Get headers
     *
     * @return ParameterBag
     */
    public function getHeadersBag(): ParameterBag
    {
        return $this->headersBag;
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
     * @param ParameterBag               $headersBag
     */
    private function __construct(
        ScheduledCommandIdentifier $id,
        AbstractCommand $command,
        DateTime $dateTime,
        ParameterBag $headersBag
    )
    {
        $this->id = $id;
        $this->command = $command;
        $this->date = $dateTime;
        $this->headersBag = $headersBag;
    }
}
