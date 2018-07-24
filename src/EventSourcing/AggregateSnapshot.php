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

namespace Desperado\ServiceBus\EventSourcing;

/**
 * Snapshot
 */
final class AggregateSnapshot
{
    /**
     * Aggregate
     *
     * @var Aggregate
     */
    private $aggregate;

    /**
     * Aggregate version
     *
     * @var int
     */
    private $version;

    /**
     * @param Aggregate $aggregate
     * @param int       $version
     */
    public function __construct(Aggregate $aggregate, int $version)
    {
        $this->aggregate = $aggregate;
        $this->version   = $version;
    }

    /**
     * Receive aggregate
     *
     * @return Aggregate
     */
    public function aggregate(): Aggregate
    {
        return $this->aggregate;
    }

    /**
     * Receive snapshot (aggregate) version
     *
     * @return int
     */
    public function version(): int
    {
        return $this->version;
    }
}
