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
use Desperado\ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate;
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
    public $id;

    /**
     * Scheduled message
     *
     * @var Command
     */
    public $command;

    /**
     * Execution date
     *
     * @var \DateTimeImmutable
     */
    public $date;

    /**
     * The message was sent to the transport
     *
     * @var bool
     */
    public $isSent;

    /**
     * @param ScheduledOperationId $id
     * @param Command              $command
     * @param \DateTimeImmutable   $dateTime
     *
     * @return self
     */
    public static function new(ScheduledOperationId $id, Command $command, \DateTimeImmutable $dateTime): self
    {
        self::validateDatetime($dateTime);

        return new self($id, $command, $dateTime);
    }

    /**
     * @param array{processing_date:string, command:string, id:string, is_sent:bool} $data
     *
     * @return ScheduledOperation
     */
    public static function restoreFromRow(array $data): self
    {
        /** @var \DateTimeImmutable $dateTime */
        $dateTime = datetimeInstantiator($data['processing_date']);

        /** @var Command $command */
        $command = \unserialize(\base64_decode($data['command']), ['allowed_classes' => true]);

        return new self(
            new ScheduledOperationId($data['id']),
            $command,
            $dateTime,
            (bool) $data['is_sent']
        );
    }

    /**
     * @param ScheduledOperationId $id
     * @param Command              $command
     * @param \DateTimeImmutable   $dateTime
     * @param bool                 $isSent
     *
     * @throws  \Desperado\ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate
     */
    private function __construct(ScheduledOperationId $id, Command $command, \DateTimeImmutable $dateTime, bool $isSent = false)
    {
        $this->id      = $id;
        $this->command = $command;
        $this->date    = $dateTime;
        $this->isSent  = $isSent;
    }

    /**
     * @param \DateTimeImmutable $dateTime
     *
     * @return void
     *
     * @throws  \Desperado\ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate
     */
    private static function validateDatetime(\DateTimeImmutable $dateTime): void
    {
        /** @var \DateTimeImmutable $currentDate */
        $currentDate = datetimeInstantiator('NOW');

        if($currentDate >= $dateTime)
        {
            throw new InvalidScheduledOperationExecutionDate(
                'The date of the scheduled task should be greater than the current one'
            );
        }
    }
}
