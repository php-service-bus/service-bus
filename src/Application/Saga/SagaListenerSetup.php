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

use Desperado\Framework\Application\Saga\Exceptions\SagaAnnotationException;
use Desperado\Framework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\Framework\Infrastructure\CQRS\Context\Options\EventOptions;
use Desperado\Framework\Infrastructure\CQRS\MessageBus\MessageBusBuilder;
use Desperado\Framework\Infrastructure\EventSourcing\Annotation\Saga;
use Desperado\Framework\Infrastructure\EventSourcing\Annotation\SagaListener;
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
     * Saga annotations reader
     *
     * @var SagaAnnotationReader
     */
    private $sagaAnnotationsReader;

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

        $this->sagaAnnotationsReader = new SagaAnnotationReader($annotationReader, $logger);
    }

    /**
     * Extract and configure listeners for specified saga
     *
     * @param string $saga
     *
     * @return void
     *
     * @throws SagaAnnotationException
     */
    public function setup(string $saga): void
    {
        $headerDeclaration = $this->sagaAnnotationsReader->extractHeaderDeclaration($saga);

        if(null === $headerDeclaration)
        {
            throw new SagaAnnotationException(
                \sprintf('The class-level annotation "%s" is not specified', Saga::class)
            );
        }

        self::guardDeclaredIdentity($saga, $headerDeclaration);

        foreach($this->sagaAnnotationsReader->extractEventListenersAnnotation($saga) as $annotationData)
        {
            /** @var SagaListener $annotation */
            $annotation = $annotationData['annotation'];

            $annotation->containingIdentityProperty = '' !== (string) $annotation->containingIdentityProperty
                ? $annotation->containingIdentityProperty
                : $headerDeclaration->containingIdentityProperty;

            self::guardContainingIdentityProperty($saga, $annotation);
            self::guardStorageManagerConfigured($saga, $this->storageManagers);

            /** @var \ReflectionParameter[] $parameters */
            $parameters = $annotationData['arguments'];

            $eventNamespace = $parameters[0]->getClass()->getName();

            $handler = new SagaEventHandler(
                $headerDeclaration->identityNamespace,
                $annotation,
                $this->storageManagers[$saga]
            );

            $this->messageBusBuilder->addMessageHandler(
                $eventNamespace,
                \Closure::fromCallable($handler),
                new EventOptions()
            );

            $this->logger->debug(
                \sprintf(
                    'Event listener for saga "%s" ("%s") successful registered',
                    $saga, $eventNamespace

                )
            );
        }
    }

    /**
     * Assert containing identity value is specified
     *
     * @param string       $saga
     * @param SagaListener $annotation
     *
     * @return void
     *
     * @throws SagaAnnotationException
     */
    private static function guardContainingIdentityProperty(string $saga, SagaListener $annotation): void
    {
        if('' === (string) $annotation->containingIdentityProperty)
        {
            throw new SagaAnnotationException(
                \sprintf(
                    '"containingIdentityProperty" value must be specified for saga "%s"', $saga
                )
            );
        }
    }

    /**
     * Guard identity namespace is correct
     *
     * @param string $saga
     * @param Saga   $annotation
     *
     * @return void
     *
     * @throws SagaAnnotationException
     */
    private static function guardDeclaredIdentity(string $saga, Saga $annotation): void
    {
        if('' === (string) $annotation->identityNamespace)
        {
            throw new SagaAnnotationException(
                \sprintf(
                    '"identityNamespace" value must be specified for saga "%s"', $saga
                )
            );
        }

        if(false === \class_exists($annotation->identityNamespace))
        {
            throw new SagaAnnotationException(
                \sprintf(
                    '"identityNamespace" value must be contains exists identity class for saga "%s"', $saga
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
     * @throws SagaAnnotationException
     */
    private static function guardStorageManagerConfigured(string $saga, array $managers)
    {
        if(false === \array_key_exists($saga, $managers))
        {
            throw new SagaAnnotationException(
                \sprintf('Storage manager for saga "%s" was not configured', $saga)
            );
        }
    }
}
