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

namespace Desperado\ServiceBus\EventSourcing\EventStreamStore;

use Amp\Promise;
use Desperado\ServiceBus\EventSourcing\Aggregate;
use Desperado\ServiceBus\EventSourcing\AggregateId;

/**
 * Aggregates store
 */
interface AggregateStore
{
    /**
     * Save new event stream
     *
     * @param StoredAggregateEventStream $aggregateEventStream
     * @param callable                   $afterSaveHandler  The handler is called after the data is saved. If
     *                                                      transactions are supported, it will be called before the
     *                                                      commit
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\NonUniqueStreamId
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\SaveStreamFailed
     */
    public function saveStream(StoredAggregateEventStream $aggregateEventStream, callable $afterSaveHandler): Promise;

    /**
     * Append events to exists stream
     *
     * @param StoredAggregateEventStream $aggregateEventStream
     * @param callable                   $afterSaveHandler  The handler is called after the data is saved. If
     *                                                      transactions are supported, it will be called before the
     *                                                      commit
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\SaveStreamFailed
     */
    public function appendStream(StoredAggregateEventStream $aggregateEventStream, callable $afterSaveHandler): promise;

    /**
     * Load event stream
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<\Desperado\ServiceBus\EventSourcing\Store\StoredAggregateEventStream|null>
     *
     * @param AggregateId $id
     * @param int         $fromVersion
     * @param int|null    $toVersion
     *
     * @return Promise
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\LoadStreamFailed
     */
    public function loadStream(
        AggregateId $id,
        int $fromVersion = Aggregate::START_PLAYHEAD_INDEX,
        ?int $toVersion = null
    ): Promise;

    /**
     * Marks stream closed
     *
     * @param AggregateId $id
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\CloseStreamFailed
     */
    public function closeStream(AggregateId $id): Promise;
}
