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
use function Desperado\ServiceBus\Common\datetimeInstantiator;
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
     * @param array<id:string, processing_date:string, command:string> $data
     *
     * @return ScheduledOperation
     */
    public static function fromRow(array $data): self
    {
        /** @var \DateTimeImmutable $dateTime */
        $dateTime = datetimeInstantiator($data['processing_date']);

        /** @var Command $command */
        $command = \unserialize(\base64_decode($data['command']), ['allowed_classes' => true]);

        return new self(
            new ScheduledOperationId($data['id']),
            $command,
            $dateTime
        );
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
}
