<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\EventSourcing\Storage\Doctrine;

use Desperado\Framework\Domain\DateTime;
use Desperado\Framework\Domain\Event\StoredRepresentation\StoredDomainEvent;
use Desperado\Framework\Domain\Event\StoredRepresentation\StoredEventStream;
use Desperado\Framework\Domain\Identity\IdentityInterface;
use Desperado\Framework\Infrastructure\EventSourcing\Storage\Configuration\StorageConfigurationConfig;
use Desperado\Framework\Infrastructure\EventSourcing\Storage\EventStorageInterface;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

/**
 * Synchronous (!!) storage implementation
 */
class SyncDoctrineStorage implements EventStorageInterface
{
    /**
     * Doctrine connection instance
     *
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    /**
     * @param StorageConfigurationConfig $storage
     */
    public function __construct(StorageConfigurationConfig $storage)
    {
        $this->connection = DriverManager::getConnection([
            'dbname'   => $storage->getDatabase(),
            'user'     => $storage->getAuth()->getUsername(),
            'password' => $storage->getAuth()->getPassword(),
            'host'     => $storage->getHost()->getHost(),
            'driver'   => 'doctrinePgSql' === $storage->getDriver() ? 'pdo_pgsql' : 'pdo_mysql'
        ],
            new Configuration()
        );
        $this->initDatabaseTables();
    }

    /**
     * @inheritdoc
     */
    public function save(
        StoredEventStream $storedEventStream,
        callable $onSaved = null,
        callable $onFailed = null
    ): void
    {
        try
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

            $this->connection->transactional(
                function() use ($eventsRows, $storedEventStream)
                {
                    $this->connection->executeQuery(
                        'INSERT INTO event_store_streams (id, identity_class, is_closed) VALUES (?, ?, ?) '
                        . 'ON CONFLICT (id) DO UPDATE SET is_closed = ?;',
                        [
                            $storedEventStream->getId(),
                            $storedEventStream->getClass(),
                            (int) $storedEventStream->isClosed(),
                            (int) $storedEventStream->isClosed()
                        ]
                    );

                    foreach($eventsRows as $insertEntry)
                    {
                        $query = 'INSERT INTO event_store_events (id, stream_id, playhead, occurred_at, recorded_at, payload) '
                            . 'VALUES (?, ?, ?, ?, ?, ?)';

                        $this->connection->executeQuery($query, $insertEntry);
                    }
                }
            );

            if(null !== $onFailed)
            {
                $onFailed();
            }
        }
        catch(\Throwable $throwable)
        {
            if(null !== $onFailed)
            {
                $onFailed($throwable);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function load(IdentityInterface $id, callable $onLoaded, callable $onFailed = null): void
    {
        $this->loadFromPlayhead($id, 0, $onLoaded, $onFailed);
    }

    /**
     * @inheritdoc
     */
    public function loadFromPlayhead(
        IdentityInterface $id,
        int $playheadPosition,
        callable $onLoaded,
        callable $onFailed = null
    ): void
    {
        try
        {
            $eventStreamEvents = $this->connection
                ->createQueryBuilder()
                ->select('events.*, streams.id, streams.identity_class, streams.is_closed')
                ->from('event_store_streams', 'streams')
                ->innerJoin('streams', 'event_store_events', 'events', 'events.stream_id = streams.id')
                ->where('streams.id = ?')
                ->andWhere('identity_class = ?')
                ->setParameters([$id->toString(), \get_class($id)])
                ->execute()
                ->fetchAll();

            $events = [];
            $isClosed = false;

            \array_map(
                function(array $eachEvent) use (&$events, &$isClosed, $playheadPosition)
                {
                    if($playheadPosition <= $eachEvent['playhead'])
                    {
                        $isClosed = $eachEvent['is_closed'];
                        $events[$eachEvent['playhead']] = [
                            'id'            => $eachEvent['id'],
                            'playhead'      => $eachEvent['playhead'],
                            'occurredAt'    => $eachEvent['occurred_at'],
                            'recordedAt'    => $eachEvent['recorded_at'],
                            'receivedEvent' => $eachEvent['payload']
                        ];
                    }
                },
                $eventStreamEvents
            );

            $onLoaded(['isClosed' => $isClosed, 'events' => $events]);
        }
        catch(\Throwable $throwable)
        {
            if(null !== $onFailed)
            {
                $onFailed($throwable);
            }
        }
    }

    /**
     * Init sql tables
     *
     * @return void
     */
    private function initDatabaseTables(): void
    {
        $this->connection->transactional(
            function()
            {
                $schemeData = file_get_contents(__DIR__ . '/pg_sql_scheme.sql');
                $queryParts = \array_map('trim', \explode(\PHP_EOL . \PHP_EOL, $schemeData));

                foreach($queryParts as $query)
                {
                    $this->connection->query($query);
                }
            }
        );
    }
}
