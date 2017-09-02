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

namespace Desperado\ConcurrencyFramework\Application\Module;

use Desperado\ConcurrencyFramework\Application\Saga\SagaListenerSetup;
use Desperado\ConcurrencyFramework\Application\Storage\StorageManagerRegistry;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\MessageBus\MessageBusBuilder;
use Psr\Log\LoggerInterface;

/**
 * Sagas support
 */
class SagasModule extends AbstractModule
{
    /**
     * Storage managers
     *
     * @var StorageManagerRegistry
     */
    private $storageRegistry;

    /**
     * Sagas namespace
     *
     * @var array
     */
    private $sagas = [];

    /**
     * @param array                  $sagas
     * @param StorageManagerRegistry $storageRegistry
     * @param LoggerInterface        $logger
     */
    public function __construct(array $sagas, StorageManagerRegistry $storageRegistry, LoggerInterface $logger)
    {
        parent::__construct($logger);

        $this->sagas = $sagas;
        $this->storageRegistry = $storageRegistry;
    }

    /**
     * @inheritdoc
     */
    public function boot(MessageBusBuilder $messageBusBuilder, AnnotationReader $annotationsReader): void
    {
        $sagaListenerConfigurator = new SagaListenerSetup(
            $messageBusBuilder, $annotationsReader,
            $this->storageRegistry->getSagaStorageManagers(), $this->getLogger()
        );

        foreach($this->sagas as $saga)
        {
            $sagaListenerConfigurator->setup($saga);
        }
    }
}
