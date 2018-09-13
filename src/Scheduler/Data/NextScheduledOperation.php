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
use function Desperado\ServiceBus\Common\datetimeToString;
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
    private $id;

    /**
     * Time in milliseconds
     *
     * @var string
     */
    private $time;

    /**
     * @param ScheduledOperationId $id
     * @param \DateTimeImmutable   $time
     */
    public function __construct(ScheduledOperationId $id, \DateTimeImmutable $time)
    {
        $this->id   = $id;
        $this->time = (string) datetimeToString($time);
    }

    /**
     * @return ScheduledOperationId
     */
    public function id(): ScheduledOperationId
    {
        return $this->id;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function time(): \DateTimeImmutable
    {
        /** @var \DateTimeImmutable $datetime */
        $datetime = datetimeInstantiator($this->time);

        return $datetime;
    }
}
