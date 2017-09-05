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

namespace Desperado\Framework\Application\Storage;

use Desperado\Framework\Common\Formatter\ThrowableFormatter;
use Desperado\Framework\Domain\ParameterBag;
use Desperado\Framework\Domain\Serializer\MessageSerializerInterface;
use Desperado\Framework\Infrastructure\EventSourcing\EventStore\EventStore;
use Desperado\Framework\Infrastructure\EventSourcing\Repository\AggregateRepository;
use Desperado\Framework\Infrastructure\EventSourcing\Repository\SagaRepository;
use Desperado\Framework\Infrastructure\EventSourcing\Storage\StorageFactory;
use Desperado\Framework\Infrastructure\StorageManager\AggregateStorageManager;
use Desperado\Framework\Infrastructure\StorageManager\EntityManager;
use Desperado\Framework\Infrastructure\StorageManager\SagaStorageManager;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Storage manager factory
 */
class StorageManagerFactory
{
    public const TYPE_SAGAS = 'sagas';
    public const TYPE_AGGREGATES = 'aggregates';
    public const TYPE_ENTITIES = 'entities';

    private const MAP = [
        self::TYPE_SAGAS      => [
            'repository' => SagaRepository::class,
            'manager'    => SagaStorageManager::class
        ],
        self::TYPE_AGGREGATES => [
            'repository' => AggregateRepository::class,
            'manager'    => AggregateStorageManager::class
        ]
    ];

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * Storages registry
     *
     * @var $storageManagerRegistry
     */
    private $storageManagerRegistry;

    /**
     * @param StorageManagerRegistry     $storageManagerRegistry
     * @param LoggerInterface            $logger
     * @param MessageSerializerInterface $messageSerializer
     */
    public function __construct(
        StorageManagerRegistry $storageManagerRegistry,
        LoggerInterface $logger,
        MessageSerializerInterface $messageSerializer
    )
    {
        $this->storageManagerRegistry = $storageManagerRegistry;
        $this->logger = $logger;
        $this->messageSerializer = $messageSerializer;
    }

    /**
     * Append ORM managers
     *
     * @param array        $entities
     * @param Connection[] $connections
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function appendEntities(array $entities, array $connections): void
    {
        try
        {
            foreach($entities as $connection => $connectionEntities)
            {
                if(isset($connections[$connection]))
                {
                    foreach($connectionEntities as $entity)
                    {
                        $this->storageManagerRegistry->add(
                            $entity,
                            new EntityManager(
                                $entity,
                                $connections[$connection]
                            )
                        );
                    }
                }
            }
        }
        catch(\Throwable $throwable)
        {
            $this->logger->error(ThrowableFormatter::toString($throwable));

            throw $throwable;
        }
    }

    /**
     * Append event sourced entries
     *
     * @param string       $type
     * @param ParameterBag $eventSourcedConfig
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function appendEventSourced(string $type, ParameterBag $eventSourcedConfig)
    {
        $eventSourcedEntriesList = new ParameterBag(((array) $eventSourcedConfig->get('list', [])));
        $connectionDSN = $eventSourcedConfig->getAsString('storageDSN');

        try
        {
            if(0 === $eventSourcedEntriesList->count())
            {
                $this->logger->debug(\sprintf('%s list is empty, no configuration required', $type));

                return;
            }

            if('' === (string) $connectionDSN)
            {
                throw new \InvalidArgumentException(
                    \sprintf('%s storage connection DSN can\'t be empty', $type)
                );
            }

            $repositoryNamespace = self::MAP[$type]['repository'];
            $managerNamespace = self::MAP[$type]['manager'];

            $repository = new $repositoryNamespace(
                new EventStore(
                    StorageFactory::create($connectionDSN),
                    $this->messageSerializer
                )
            );

            foreach($eventSourcedEntriesList as $eventSourced)
            {
                $this->storageManagerRegistry->add(
                    $eventSourced,
                    new $managerNamespace($eventSourced, $repository)
                );
            }
        }
        catch(\Throwable $throwable)
        {
            $this->logger->error(ThrowableFormatter::toString($throwable));

            throw $throwable;
        }
    }
}
