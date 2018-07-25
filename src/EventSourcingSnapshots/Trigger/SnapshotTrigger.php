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

namespace Desperado\ServiceBus\EventSourcingSnapshots\Trigger;

use Desperado\ServiceBus\EventSourcing\Aggregate;
use Desperado\ServiceBus\EventSourcing\AggregateSnapshot;

/**
 * Snapshot trigger
 */
interface SnapshotTrigger
{
    /**
     * A snapshot must be created?
     *
     * @param Aggregate         $aggregate
     * @param AggregateSnapshot $previousSnapshot
     *
     * @return bool
     */
    public function snapshotMustBeCreated(
        Aggregate $aggregate,
        AggregateSnapshot $previousSnapshot = null
    ): bool;
}
