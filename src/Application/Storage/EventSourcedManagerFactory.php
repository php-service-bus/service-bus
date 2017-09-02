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

namespace Desperado\ConcurrencyFramework\Application\Storage;

use Desperado\ConcurrencyFramework\Common\Formatter\ThrowableFormatter;
use Desperado\ConcurrencyFramework\Domain\ParameterBag;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\EventStore\EventStore;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Repository\AggregateRepository;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Repository\SagaRepository;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\StorageFactory;
use Desperado\ConcurrencyFramework\Infrastructure\StorageManager\AggregateStorageManager;
use Desperado\ConcurrencyFramework\Infrastructure\StorageManager\SagaStorageManager;
use Psr\Log\LoggerInterface;

/**
 * Saga storage manager factory
 */
class EventSourcedManagerFactory
{
    public const TYPE_SAGAS = 'sagas';
    public const TYPE_AGGREGATES = 'aggregates';

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
     * Type event sourced entry
     *
     * @var string
     */
    private $type;

    /**
     * Event sourcing config
     *
     * @var ParameterBag
     */
    private $configSection;

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
     * Create factory for sagas
     *
     * @param ParameterBag               $configSection
     * @param MessageSerializerInterface $messageSerializer
     *
     * @return EventSourcedManagerFactory
     */
    public static function sagas(ParameterBag $configSection, MessageSerializerInterface $messageSerializer): self
    {
        return new self(self::TYPE_SAGAS, $configSection, $messageSerializer);
    }

    /**
     * Create factory for aggregates
     *
     * @param ParameterBag               $configSection
     * @param MessageSerializerInterface $messageSerializer
     *
     * @return EventSourcedManagerFactory
     */
    public static function aggregates(ParameterBag $configSection, MessageSerializerInterface $messageSerializer): self
    {
        return new self(self::TYPE_AGGREGATES, $configSection, $messageSerializer);
    }

    /**
     * Append event sourced storages
     *
     * @param StorageManagerRegistry $storageManagerRegistry
     *
     * @return void
     */
    public function append(StorageManagerRegistry $storageManagerRegistry): void
    {
        try
        {
            $eventSourcedList = $this->getSagasList();
            $connectionDSN = $this->getSagaStorageDSN();

            if(0 === \count($eventSourcedList))
            {
                $this->logger->debug(\sprintf('%s list is empty, no configuration required', $this->type));

                return;
            }

            if(null === $connectionDSN)
            {
                throw new \InvalidArgumentException(\sprintf('%s storage connection DSN can\'t be empty', $this->type));
            }

            $repositoryNamespace = self::MAP[$this->type]['repository'];
            $managerNamespace = self::MAP[$this->type]['manager'];

            $sagaRepository = new $repositoryNamespace(
                new EventStore(
                    StorageFactory::create($connectionDSN),
                    $this->messageSerializer
                )
            );

            foreach($eventSourcedList as $eventSourced)
            {
                $storageManagerRegistry->add(
                    $eventSourced,
                    new $managerNamespace($eventSourced, $sagaRepository)
                );
            }
        }
        catch(\Throwable $throwable)
        {
            $this->logger->error(ThrowableFormatter::toString($throwable));
        }
    }

    /**
     * @param string                     $type
     * @param ParameterBag               $configSection
     * @param MessageSerializerInterface $messageSerializer
     */
    private function __construct(
        string $type,
        ParameterBag $configSection,
        MessageSerializerInterface $messageSerializer
    )
    {
        $this->type = $type;
        $this->configSection = $configSection;
        $this->messageSerializer = $messageSerializer;
    }

    /**
     * Get list
     *
     * @return array
     */
    private function getSagasList(): array
    {
        $sagas = $this->configSection->get('list', []);

        return true === \is_array($sagas) ? $sagas : [];
    }

    /**
     * Get storage connection DSN
     *
     * @return null|string
     */
    private function getSagaStorageDSN(): ?string
    {
        $connectionDSN = $this->configSection->getAsString('storageDSN');

        return '' !== (string) $connectionDSN ? $connectionDSN : null;
    }
}
