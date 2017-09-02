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

namespace Desperado\Framework\Application\Saga;

use Desperado\Framework\Application\Saga\Exceptions\EventListenerAnnotationException;
use Desperado\Framework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\Framework\Infrastructure\CQRS\Context\Options\EventOptions;
use Desperado\Framework\Infrastructure\CQRS\MessageBus\MessageBusBuilder;
use Desperado\Framework\Infrastructure\StorageManager\SagaStorageManager;
use Psr\Log\LoggerInterface;

/**
 * Setup saga event listeners
 */
class SagaListenerSetup
{
    /**
     * Message bus builder
     *
     * @var MessageBusBuilder
     */
    private $messageBusBuilder;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Saga storage manager
     *
     * @var SagaStorageManager[]
     */
    private $storageManagers = [];

    /**
     * Event listeners reader
     *
     * @var SagaEventListenersAnnotationReader
     */
    private $eventListenersReader;

    /**
     * @param MessageBusBuilder    $messageBusBuilder
     * @param AnnotationReader     $annotationReader
     * @param SagaStorageManager[] $storageManagers
     * @param LoggerInterface      $logger
     */
    public function __construct(
        MessageBusBuilder $messageBusBuilder,
        AnnotationReader $annotationReader,
        array $storageManagers,
        LoggerInterface $logger
    )
    {
        $this->messageBusBuilder = $messageBusBuilder;
        $this->storageManagers = $storageManagers;
        $this->logger = $logger;

        $this->eventListenersReader = new SagaEventListenersAnnotationReader($annotationReader, $logger);
    }

    /**
     * Extract and configure listeners for specified saga
     *
     * @param string $saga
     *
     * @return void
     */
    public function setup(string $saga): void
    {
        foreach($this->eventListenersReader->extractEventListenersAnnotation($saga) as $annotationData)
        {
            self::guardStorageManagerConfigured($saga, $this->storageManagers);

            /** @var \ReflectionParameter[] $parameters */
            $parameters = $annotationData['arguments'];

            $eventNamespace = $parameters[0]->getClass()->getName();

            $handler = new SagaEventHandler(
                $annotationData['annotation'],
                $this->storageManagers[$saga]
            );

            $this->messageBusBuilder->addMessageHandler($eventNamespace, $handler, new EventOptions());

            $this->logger->debug(
                \sprintf(
                    'Event listener for saga "%s" ("%s") successful registered',
                    $saga, $eventNamespace

                )
            );
        }
    }

    /**
     * Assert storage manager for saga exists
     *
     * @param string $saga
     * @param array  $managers
     *
     * @return void
     * @throws EventListenerAnnotationException
     */
    private static function guardStorageManagerConfigured(string $saga, array $managers)
    {
        if(false === \array_key_exists($saga, $managers))
        {
            throw new EventListenerAnnotationException(
                \sprintf('Storage manager for saga "%s" was not configured', $saga)
            );
        }
    }
}
