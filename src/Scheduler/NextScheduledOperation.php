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
     * Get identifier
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get time in microseconds
     *
     * @return int
     */
    public function getTime(): int
    {
        return $this->time;
    }
}
