<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\PostgreSQL;

use Amp\Postgres;
use Desperado\ConcurrencyFramework\Domain\DateTime;
use Desperado\ConcurrencyFramework\Domain\Event\StoredRepresentation\StoredDomainEvent;
use Desperado\ConcurrencyFramework\Domain\Event\StoredRepresentation\StoredEventStream;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\Configuration\StorageConfigurationConfig;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\EventStorageInterface;


/**
 * PostgreSQL event storage (AMPHP implementation)
 */
class AmPhpPostgreSQLStorage implements EventStorageInterface
{
    /**
     * Postgres deferred connection
     *
     * @var Postgres\ConnectionPool
     */
    private $connection;

    /**
     * Is event table created
     *
     * @var bool
     */
    private $eventTableCreated = false;

    /**
     * @param StorageConfigurationConfig $connectionConfig
     */
    public function __construct(StorageConfigurationConfig $connectionConfig)
    {
        $this->connection = Postgres\pool(
            \sprintf(
                'host=%s port=%d user=%s password=%s dbname=%s options=\'--client_encoding=%s\'',
                $connectionConfig->getHost()->getHost(),
                $connectionConfig->getHost()->getPort(),
                $connectionConfig->getAuth()->getUsername(),
                $connectionConfig->getAuth()->getPassword(),
                $connectionConfig->getDatabase(),
                $connectionConfig->getOptions()->getEncoding()
            )
        );

        $this->guardTablesExists();
    }

    /**
     * @inheritdoc
     */
    public function save(StoredEventStream $storedEventStream): void
    {
        $this->guardTablesExists();

        $this->saveEventStream($storedEventStream);
    }

    /**
     * @inheritdoc
     */
    public function load(IdentityInterface $id): array
    {
        $this->guardTablesExists();

        return $this->loadEventStreamData($id, 0);
    }

    /**
     * @inheritdoc
     */
    public function loadFromPlayhead(IdentityInterface $id, int $playheadPosition): array
    {
        $this->guardTablesExists();

        return $this->loadEventStreamData($id, $playheadPosition);
    }

    /**
     * Load event stream data
     *
     * @param IdentityInterface $id
     * @param int               $playheadPosition
     *
     * @return array
     */
    private function loadEventStreamData(IdentityInterface $id, int $playheadPosition)
    {
        $result = [];
        $connection = $this->connection;

        $sql = 'SELECT events.id, events.stream_id, events.playhead, events.occurred_at, events.recorded_at, events.payload, '
            . 'streams.is_closed '
            . 'FROM event_store_events AS events '
            . 'INNER JOIN event_store_streams AS streams ON events.stream_id = streams.id '
            . 'WHERE streams.id = $1 AND streams.aggregate = $2 AND events.playhead > $3';

        /** @var \Amp\Postgres\Statement $selectStatement */
        $selectStatement = $connection->prepare($sql);

        /** @var \Amp\Postgres\TupleResult $selectResult */
        $selectResult = yield $selectStatement->execute($id->toString(), \get_class($id), $playheadPosition);

        while(yield $selectResult->advance())
        {
            $dataRow = $selectResult->getCurrent();

            if(false === isset($result['isClosed']))
            {
                $result['isClosed'] = $dataRow['is_closed'];
            }

            $result['events'][$dataRow['playhead']] = [
                'id'            => $dataRow['id'],
                'playhead'      => $dataRow['playhead'],
                'receivedEvent' => $dataRow['payload'],
                'occurredAt'    => DateTime::fromString($dataRow['occurred_at']),
                'recordedAt'    => DateTime::fromString($dataRow['recorded_at'])
            ];
        }

        return $result;
    }

    /**
     * Save event stream data
     *
     * @param StoredEventStream $storedEventStream
     *
     * @return \Generator
     *
     * @throws \Throwable
     */
    private function saveEventStream(StoredEventStream $storedEventStream): \Generator
    {
        $connection = $this->connection;

        /** @var \Amp\Postgres\Transaction $transaction */
        $transaction = yield $connection->transaction();

        try
        {
            /** @var \Amp\Postgres\Statement $eventStreamStatement */
            $eventStreamStatement = yield $transaction->prepare(
                'INSERT INTO event_store_streams (id, aggregate, is_closed) '
                . 'VALUES ($1, $2, $3) '
                . 'ON CONFLICT (id) DO UPDATE '
                . 'SET is_closed = $4;'
            );

            yield $eventStreamStatement->execute(
                $storedEventStream->getId(),
                $storedEventStream->getClass(),
                $storedEventStream->isClosed(),
                $storedEventStream->isClosed()
            );

            $queryData = $this->createSaveEventsQuery($storedEventStream);

            /** @var \Amp\Postgres\Statement $eventStoreStatement */
            $eventStoreStatement = yield $transaction->prepare($queryData['query']);

            yield $eventStoreStatement->execute($queryData['parameters']);

            yield $transaction->commit();
        }
        catch(\Throwable $throwable)
        {
            yield $transaction->rollback();

            throw $throwable;
        }
    }

    /**
     * Create save events multi query
     *
     * [
     *     'query'      => 'queryString',
     *     'parameters' => 'statements'
     * ]
     *
     * @param StoredEventStream $storedEventStream
     *
     * @return array
     */
    private function createSaveEventsQuery(StoredEventStream $storedEventStream)
    {
        $eventsRows = [];

        \array_map(
            function(StoredDomainEvent $storedDomainEvent) use ($storedEventStream, &$eventsRows)
            {
                $row[] = [
                    $storedDomainEvent->getId(),
                    $storedEventStream->getId(),
                    $storedDomainEvent->getPlayhead(),
                    $storedDomainEvent->getOccurredAt(),
                    DateTime::nowToString(),
                    $storedDomainEvent->getReceivedEvent()

                ];

                $eventsRows = array_merge($eventsRows, $row);
            },
            $storedEventStream->getEvents()
        );

        return PgSqlQueryUtils::createMultiInsertQuery(
            'event_store_events',
            ['id', 'stream_id', 'playhead', 'occurred_at', 'recorded_at', 'payload'],
            $eventsRows
        );
    }

    /**
     * Guard table created
     *
     * @return void
     */
    private function guardTablesExists(): void
    {
        if(false === $this->eventTableCreated)
        {
            $this->initTables();
        }
    }

    /**
     * Init event store tables
     *
     * @return \Generator
     */
    private function initTables(): \Generator
    {
        $connection = $this->connection;

        yield $connection->query(
            (string) \file_get_contents(__DIR__ . '/event_store.sql')
        );
    }
}
