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

/**
 * Scheduled job data (for next job)
 */
final class NextScheduledOperation
{
    /**
     * Job identifier
     *
     * @var string
     */
    private $id;

    /**
     * Time in milliseconds
     *
     * @var int
     */
    private $time;

    /**
     * @param string $id
     * @param int    $time
     */
    public function __construct(string $id, int $time)
    {
        $this->id = $id;
        $this->time = $time;
    }

    /**
     * Receive identifier
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Receive time in microseconds
     *
     * @return int
     */
    public function time(): int
    {
        return $this->time;
    }
}
