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
 * Generation of snapshots every N versions
 */
class SnapshotVersionTrigger implements SnapshotTrigger
{
    private const DEFAULT_VERSION_STEP = 10;

    /**
     * Version step interval
     *
     * @var int
     */
    private $stepInterval;

    /**
     * @param int $stepInterval
     */
    public function __construct($stepInterval = self::DEFAULT_VERSION_STEP)
    {
        $this->stepInterval = $stepInterval;
    }

    /**
     * @inheritdoc
     */
    public function snapshotMustBeCreated(Aggregate $aggregate, AggregateSnapshot $previousSnapshot = null): bool
    {
        if(null === $previousSnapshot)
        {
            return true;
        }

        return $this->stepInterval <= ($aggregate->version() - $previousSnapshot->version);
    }
}
