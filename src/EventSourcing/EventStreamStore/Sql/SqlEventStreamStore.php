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

namespace Desperado\ServiceBus\EventSourcing\EventStreamStore\Sql;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\NonUniqueStreamId;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\SaveStreamFailed;
use Desperado\ServiceBus\Infrastructure\Storage\QueryExecutor;
use function Latitude\QueryBuilder\field;
use Desperado\ServiceBus\EventSourcing\Aggregate;
use Desperado\ServiceBus\EventSourcing\AggregateId;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\AggregateStore;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEvent;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEventStream;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchAll;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchOne;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\insertQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\selectQuery;
use function Desperado\ServiceBus\Infrastructure\Storage\SQL\updateQuery;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed;

/**
 * Events storage backend (SQL-based)
 */
final class SqlEventStreamStore implements AggregateStore
{
    /**
     * @var StorageAdapter
     */
    private $adapter;

    /**
     * @param StorageAdapter $adapter
     */
    public function __construct(StorageAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @inheritdoc
     */
    public function saveStream(StoredAggregateEventStream $aggregateEventStream): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(StoredAggregateEventStream $aggregateEventStream) use ($adapter): \Generator
            {
                /** @var \Desperado\ServiceBus\Infrastructure\Storage\TransactionAdapter $transaction */
                $transaction = yield $adapter->transaction();

                try
                {
                    yield from self::doSaveStream($transaction, $aggregateEventStream);
                    yield from self::doSaveEvents($transaction, $aggregateEventStream);

                    yield $transaction->commit();
                }
                catch(UniqueConstraintViolationCheckFailed $exception)
                {
                    yield $transaction->rollback();

                    throw new NonUniqueStreamId(
                        $aggregateEventStream->aggregateId,
                        $aggregateEventStream->aggregateIdClass
                    );
                }
                catch(\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    throw new SaveStreamFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
                finally
                {
                    unset($transaction);
                }
            },
            $aggregateEventStream
        );
    }

    /**
     * @inheritdoc
     */
    public function appendStream(StoredAggregateEventStream $aggregateEventStream): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(StoredAggregateEventStream $aggregateEventStream) use ($adapter): \Generator
            {
                /** @var \Desperado\ServiceBus\Infrastructure\Storage\TransactionAdapter $transaction */
                $transaction = yield $adapter->transaction();

                try
                {
                    yield from self::doSaveEvents($transaction, $aggregateEventStream);

                    yield $transaction->commit();
                }
                catch(\Throwable $throwable)
                {
                    yield $transaction->rollback();

                    throw new SaveStreamFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
                finally
                {
                    unset($transaction);
                }
            },
            $aggregateEventStream
        );
    }

    /**
     * @inheritdoc
     */
    public function loadStream(
        AggregateId $id,
        int $fromVersion = Aggregate::START_PLAYHEAD_INDEX,
        ?int $toVersion = null
    ): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(AggregateId $id, int $fromVersion, ?int $toVersion) use ($adapter): \Generator
            {
                $aggregateEventStream = null;

                /** @var array<string, string>|null $streamData */
                $streamData = yield from self::doLoadStream($adapter, $id);

                if(null !== $streamData)
                {
                    /** @var array<int, array>|null $streamEventsData */
                    $streamEventsData = yield from self::doLoadStreamEvents(
                        $adapter,
                        (string) $streamData['id'],
                        $fromVersion,
                        $toVersion
                    );

                    $aggregateEventStream = self::restoreEventStream($adapter, $streamData, $streamEventsData);
                }

                return $aggregateEventStream;
            },
            $id, $fromVersion, $toVersion
        );
    }

    /**
     * @inheritdoc
     */
    public function closeStream(AggregateId $id): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(AggregateId $id) use ($adapter): \Generator
            {
                /**
                 * @var \Latitude\QueryBuilder\Query\UpdateQuery $updateQuery
                 *
                 * @psalm-suppress ImplicitToStringCast
                 */
                $updateQuery = updateQuery('event_store_stream', ['closed_at' => \date('Y-m-d H:i:s')])
                    ->where(equalsCriteria('id', $id))
                    ->andWhere(equalsCriteria('identifier_class', \get_class($id)));

                /** @var \Latitude\QueryBuilder\Query $compiledQuery */
                $compiledQuery = $updateQuery->compile();

                yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());
            },
            $id
        );
    }

    /**
     * Store the parent event stream
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param QueryExecutor              $queryExecutor
     * @param StoredAggregateEventStream $eventsStream
     *
     * @return \Generator It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    private static function doSaveStream(QueryExecutor $queryExecutor, StoredAggregateEventStream $eventsStream): \Generator
    {
        /** @var \Latitude\QueryBuilder\Query\InsertQuery $insertQuery */
        $insertQuery = insertQuery('event_store_stream', [
            'id'               => $eventsStream->aggregateId,
            'identifier_class' => $eventsStream->aggregateIdClass,
            'aggregate_class'  => $eventsStream->aggregateClass,
            'created_at'       => $eventsStream->createdAt,
            'closed_at'        => $eventsStream->closedAt
        ]);

        /** @var \Latitude\QueryBuilder\Query $compiledQuery */
        $compiledQuery = $insertQuery->compile();

        yield $queryExecutor->execute($compiledQuery->sql(), $compiledQuery->params());
    }

    /**
     * Saving events in stream
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param QueryExecutor              $queryExecutor
     * @param StoredAggregateEventStream $eventsStream
     *
     * @return \Generator It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    private static function doSaveEvents(QueryExecutor $queryExecutor, StoredAggregateEventStream $eventsStream): \Generator
    {
        $eventsCount = \count($eventsStream->storedAggregateEvents);

        if(0 !== $eventsCount)
        {
            yield $queryExecutor->execute(
                self::createSaveEventQueryString($eventsCount),
                self::collectSaveEventQueryParameters($eventsStream)
            );
        }
    }

    /**
     * Create a sql query to store events
     *
     * @param int $eventsCount
     *
     * @return string
     */
    private static function createSaveEventQueryString(int $eventsCount): string
    {
        return \sprintf(
        /** @lang text */
            'INSERT INTO event_store_stream_events (id, stream_id, playhead, event_class, payload, occured_at, recorded_at) VALUES %s',
            \implode(
                ', ', \array_fill(0, $eventsCount, '(?, ?, ?, ?, ?, ?, ?)')
            )
        );
    }

    /**
     * Gathering parameters for sending to a request to save events
     *
     * @param StoredAggregateEventStream $eventsStream
     *
     * @return array
     */
    private static function collectSaveEventQueryParameters(StoredAggregateEventStream $eventsStream): array
    {
        $queryParameters = [];
        $rowSetIndex     = 0;

        /** @var array<int, string|int> $parameters */
        foreach(self::prepareEventRows($eventsStream) as $parameters)
        {
            foreach($parameters as $parameter)
            {
                $queryParameters[$rowSetIndex] = $parameter;

                $rowSetIndex++;
            }
        }

        return $queryParameters;
    }

    /**
     * Prepare events to insert
     *
     * @param StoredAggregateEventStream $eventsStream
     *
     * @return array<int, array<int, string|int>>
     */
    private static function prepareEventRows(StoredAggregateEventStream $eventsStream): array
    {
        $eventsRows = [];

        foreach($eventsStream->storedAggregateEvents as $storedAggregateEvent)
        {
            /** @var StoredAggregateEvent $storedAggregateEvent */

            $row = [
                $storedAggregateEvent->eventId,
                $eventsStream->aggregateId,
                $storedAggregateEvent->playheadPosition,
                $storedAggregateEvent->eventClass,
                \base64_encode($storedAggregateEvent->eventData),
                $storedAggregateEvent->occuredAt,
                \date('Y-m-d H:i:s')
            ];

            $eventsRows[] = $row;
        }

        return $eventsRows;
    }

    /**
     * Execute load event stream
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param StorageAdapter $adapter
     * @param AggregateId    $id
     *
     * @return \Generator<array<string, string>|null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    private static function doLoadStream(StorageAdapter $adapter, AggregateId $id): \Generator
    {
        /**
         * @var \Latitude\QueryBuilder\Query\SelectQuery $selectQuery
         *
         * @psalm-suppress ImplicitToStringCast
         */
        $selectQuery = selectQuery('event_store_stream')
            ->where(equalsCriteria('id', $id))
            ->andWhere(equalsCriteria('identifier_class', \get_class($id)));

        /** @var \Latitude\QueryBuilder\Query $compiledQuery */
        $compiledQuery = $selectQuery->compile();

        /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
        $resultSet = yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());

        /** @var array<string, string>|null $data */
        $data = yield fetchOne($resultSet);

        return $data;
    }

    /**
     * Load events for specified stream
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param StorageAdapter $adapter
     * @param string         $streamId
     * @param int            $fromVersion
     * @param int|null       $toVersion
     *
     * @return \Generator<array<int, array>>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    private static function doLoadStreamEvents(
        StorageAdapter $adapter,
        string $streamId,
        int $fromVersion,
        ?int $toVersion
    ): \Generator
    {
        /** @var \Latitude\QueryBuilder\Query\SelectQuery $selectQuery */
        $selectQuery = selectQuery('event_store_stream_events')
            ->where(field('stream_id')->eq($streamId))
            ->andWhere(field('playhead')->gte($fromVersion));

        if(null !== $toVersion && $fromVersion < $toVersion)
        {
            $selectQuery->andWhere(field('playhead')->lte($toVersion));
        }

        /** @var \Latitude\QueryBuilder\Query $compiledQuery */
        $compiledQuery = $selectQuery->compile();

        /** @var \Desperado\ServiceBus\Infrastructure\Storage\ResultSet $resultSet */
        $resultSet = yield $adapter->execute($compiledQuery->sql(), $compiledQuery->params());

        $result = yield fetchAll($resultSet);

        return $result;
    }

    /**
     * Transform events stream array data to stored representation
     *
     * @param StorageAdapter         $adapter
     * @param array<string, string>  $streamData
     * @param array<int, array>|null $streamEventsData
     *
     * @return StoredAggregateEventStream
     */
    private static function restoreEventStream(
        StorageAdapter $adapter,
        array $streamData,
        ?array $streamEventsData
    ): StoredAggregateEventStream
    {
        return new StoredAggregateEventStream(
            $streamData['id'],
            $streamData['identifier_class'],
            $streamData['aggregate_class'],
            self::restoreEvents($adapter, $streamEventsData),
            $streamData['created_at'],
            $streamData['closed_at']
        );
    }

    /**
     * Restore events from rows
     *
     * @param StorageAdapter $adapter
     * @param array|null     $eventsData
     *
     * @return array<int, \Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEvent>
     */
    private static function restoreEvents(StorageAdapter $adapter, ?array $eventsData): array
    {
        $events = [];

        if(true === \is_array($eventsData) && 0 !== \count($eventsData))
        {
            /** @var array<string, string> $eventRow */
            foreach($eventsData as $eventRow)
            {
                $playhead = (int) $eventRow['playhead'];

                $events[$playhead] = new StoredAggregateEvent(
                    $eventRow['id'],
                    $playhead,
                    \base64_decode($adapter->unescapeBinary($eventRow['payload'])),
                    $eventRow['event_class'],
                    $eventRow['occured_at'],
                    $eventRow['recorded_at']
                );
            }
        }

        return $events;
    }
}
