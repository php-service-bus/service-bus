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

use function Desperado\ServiceBus\Common\datetimeInstantiator;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;

/**
 * Scheduled job data (for next job)
 */
final class NextScheduledOperation
{
    /**
     * Job identifier
     *
     * @var ScheduledOperationId
     */
    public $id;

    /**
     * Next operation date
     *
     * @var \DateTimeImmutable
     */
    public $time;

    /**
     * @param array<string, string> $row
     *
     * @return self
     */
    public static function fromRow(array $row): self
    {
        /** @var \DateTimeImmutable $datetime */
        $datetime = datetimeInstantiator($row['processing_date']);

        return new self(
            new ScheduledOperationId($row['id']),
            $datetime
        );
    }

    /**
     * @param ScheduledOperationId $id
     * @param \DateTimeImmutable   $time
     */
    public function __construct(ScheduledOperationId $id, \DateTimeImmutable $time)
    {
        $this->id   = $id;
        $this->time = $time;
    }
}
