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

namespace Desperado\ConcurrencyFramework\Application\Saga;

use Desperado\ConcurrencyFramework\Application\Context\KernelContext;
use Desperado\ConcurrencyFramework\Domain\Annotation\AbstractAnnotation;
use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\LocalContext;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Options\EventOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\MessageBus\MessageBusBuilder;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Annotation\SagaListener;
use Desperado\ConcurrencyFramework\Infrastructure\StorageManager\AbstractStorageManager;
use Desperado\ConcurrencyFramework\Infrastructure\StorageManager\SagaStorageManager;
use Psr\Log\LoggerInterface;

/**
 * Collect saga event listeners
 */
class SagaListenerConfiguration
{
    /**
     * Message bus builder
     *
     * @var MessageBusBuilder
     */
    private $messageBusBuilder;

    /**
     * Annotation reader
     *
     * @var AnnotationReader
     */
    private $annotationReader;

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
        $this->annotationReader = $annotationReader;
        $this->storageManagers = $storageManagers;
        $this->logger = $logger;
    }

    /**
     * Extract and configure listeners for specified saga
     *
     * @param string $saga
     *
     * @return void
     */
    public function extract(string $saga): void
    {
        $contextOptions = new EventOptions();

        foreach($this->extractSupportedAnnotations($saga) as $annotationData)
        {
            /** @var SagaStorageManager $storageManager */
            $storageManager = $this->storageManagers[$saga];

            /** @var \ReflectionParameter[] $parameters */
            $parameters = $annotationData['arguments'];

            $eventNamespace = $parameters[0]->getClass()->getName();

            $handler = function(EventInterface $event, ContextInterface $context) use (
                $contextOptions, $storageManager, $annotationData
            )
            {
                /** @var SagaListener $annotation */
                $annotation = $annotationData['annotation'];

                if(
                    true === \property_exists($event, $annotation->containingIdentityProperty) &&
                    '' !== (string) $annotation->containingIdentityProperty
                )
                {
                    $identityNamespace = $annotation->identityNamespace;
                    $identity = new $identityNamespace($event->{$annotation->containingIdentityProperty});

                    $saga = $storageManager->load($identity);

                    if(null !== $saga)
                    {
                        $saga->resetUncommittedEvents();
                        $saga->resetCommands();

                        $saga->transition($event);

                        $storageManager->commit($context);

                        unset($saga);
                    }
                }
            };

            $this->messageBusBuilder->addMessageHandler(
                $eventNamespace,
                $handler,
                $contextOptions
            );

            $this->logger->debug(
                \sprintf(
                    'Handler for saga "%s" ("%s") successful registered',
                    $saga, $eventNamespace

                )
            );
        }
    }

    /**
     * Extract only supported and correct annotations
     *
     * @param string $saga
     *
     * @return array
     */
    private function extractSupportedAnnotations(string $saga): array
    {
        $list = [];
        $annotations = $this->annotationReader->loadClassMethodsAnnotation($saga);

        foreach($annotations as $annotationData)
        {
            /** @var AbstractAnnotation $annotation */
            $annotation = $annotationData['annotation'];

            /** @var \ReflectionParameter[] $parameters */
            $parameters = $annotationData['arguments'];

            if(false === ($annotation instanceof SagaListener))
            {
                $this->logger->debug(
                    \sprintf('Unsupported annotation specified for saga "%s"', $saga)
                );

                continue;
            }

            /** @var SagaListener $annotation */

            if('' === (string) $annotation->containingIdentityProperty)
            {
                $this->logger->error(
                    \sprintf(
                        '"containingIdentityProperty" value must be specified for saga "%s"', $saga
                    )
                );

                continue;
            }

            if('' === (string) $annotation->identityNamespace)
            {
                $this->logger->error(
                    \sprintf(
                        '"identityNamespace" value must be specified for saga "%s"', $saga
                    )
                );

                continue;
            }

            if(false === \class_exists($annotation->identityNamespace))
            {
                $this->logger->error(
                    \sprintf(
                        '"identityNamespace" value must be contains exists identity class for saga "%s"', $saga
                    )
                );

                continue;
            }

            if(
                false === isset($parameters[0]) ||
                null === $parameters[0]->getClass() ||
                false === $parameters[0]->getClass()->implementsInterface(EventInterface::class)
            )
            {
                $this->logger->error(
                    \sprintf(
                        'The event handler for the saga "%s" should take the first argument to the object '
                        . 'that implements the "%s" interface',
                        $saga, EventInterface::class
                    )
                );
            }

            if(false === \array_key_exists($saga, $this->storageManagers))
            {
                $this->logger->error(
                    \sprintf('Storage manager for saga "%s" was not configured', $saga)
                );

                continue;
            }

            $list[] = $annotationData;
        }

        return $list;
    }
}
