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
    public $aggregate;

    /**
     * Aggregate version
     *
     * @var int
     */
    public $version;

    /**
     * @param Aggregate $aggregate
     * @param int       $version
     */
    public function __construct(Aggregate $aggregate, int $version)
    {
        $this->aggregate = $aggregate;
        $this->version   = $version;
    }
}
