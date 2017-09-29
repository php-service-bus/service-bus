<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Application;

use Desperado\CQRS\Configuration\AnnotationsExtractor;
use Desperado\CQRS\MessageBusBuilder;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\EventStorageInterface;
use Desperado\Domain\MessageBusInterface;
use Desperado\Domain\MessageRouterInterface;
use Desperado\Domain\Serializer\MessageSerializerInterface;
use Desperado\Domain\ServiceInterface;
use Desperado\EventSourcing\AggregateRepository;
use Desperado\EventSourcing\AggregateStorageManager;
use Desperado\EventSourcing\EventStore;
use Desperado\EventSourcing\Saga\AbstractSaga;
use Desperado\EventSourcing\Saga\Configuration\AnnotationsSagaConfigurationExtractor;
use Desperado\EventSourcing\Saga\SagaRepository;
use Desperado\EventSourcing\Saga\SagaStorageManager;
use Desperado\Framework\Exceptions\EntryPointException;
use Desperado\Framework\MessageRouter;
use Desperado\Framework\Metrics\MetricsCollectorInterface;
use Desperado\Framework\Metrics\NullMetricsCollector;
use Desperado\Framework\Modules\MessageErrorHandlerModule;
use Desperado\Framework\Modules\MessageValidationModule;
use Desperado\Framework\Modules\ModuleInterface;
use Desperado\Framework\Modules\SagaModule;
use Desperado\Framework\StorageManager\StorageManagerRegistry;
use Desperado\Infrastructure\Bridge\AnnotationsReader\AnnotationsReaderInterface;
use Desperado\Infrastructure\Bridge\AnnotationsReader\DoctrineAnnotationsReader;
use Desperado\Infrastructure\Bridge\Logger\Handlers\ColorizeStdOutHandler;
use Desperado\Infrastructure\Bridge\Logger\LoggerRegistry;
use Desperado\MessageSerializer\StoreEventPayloadSerializer;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Base application bootstrap
 */
abstract class AbstractBootstrap
{
    /**
     * Application root directory path
     *
     * @var string
     */
    private $rootDirectoryPath;

    /**
     * DotEnv file path
     *
     * @var string
     */
    private $environmentFilePath;

    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Annotations reader
     *
     * @var AnnotationsReaderInterface
     */
    private $annotationsReader;

    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * A serializer for storing events in the database
     *
     * @var MessageSerializerInterface
     */
    private $storedMessageSerializer;

    /**
     * Storage managers registry
     *
     * @var StorageManagerRegistry
     */
    private $storageManagersRegistry;

    /**
     * Message router
     *
     * @var MessageRouterInterface
     */
    private $messageRouter;

    /**
     * Message bus
     *
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * Execution metrics collector
     *
     * @var MetricsCollectorInterface
     */
    private $executionMetricsCollector;

    /**
     * @param string                          $rootDirectoryPath
     * @param string                          $environmentFilePath
     * @param MessageSerializerInterface      $messageSerializer
     * @param AnnotationsReaderInterface|null $annotationsReader
     * @param MessageSerializerInterface|null $storedMessageSerializer
     */
    public function __construct(
        string $rootDirectoryPath,
        string $environmentFilePath,
        MessageSerializerInterface $messageSerializer,
        AnnotationsReaderInterface $annotationsReader = null,
        MessageSerializerInterface $storedMessageSerializer = null
    )
    {
        $this->rootDirectoryPath = \rtrim($rootDirectoryPath, '/');
        $this->environmentFilePath = $environmentFilePath;
        $this->messageSerializer = $messageSerializer;
        $this->annotationsReader = $annotationsReader ?? new DoctrineAnnotationsReader();
        $this->storedMessageSerializer = $storedMessageSerializer ?? new StoreEventPayloadSerializer($messageSerializer);

        $this->initDotEnv();
        $this->initEnvironment();
        $this->initEntryPoint();
        $this->initLoggerRegistry();

        $this->init();

        $this->initAggregatesStorage();
        $this->initMessageRouter();

        if(true === \class_exists(AbstractSaga::class))
        {
            $this->initSagaStorage();
        }

        $this->executionMetricsCollector = $this->getMetricsCollector();
    }

    /**
     * Custom components initialization
     *
     * @return void
     */
    protected function init(): void
    {

    }

    /**
     * Get execution metrics collector
     *
     * @return MetricsCollectorInterface
     */
    protected function getMetricsCollector(): MetricsCollectorInterface
    {
        return new NullMetricsCollector();
    }

    /**
     * Get messages router configuration
     *
     * [
     *     'someEventNamespace'   => 'someDestinationExchange',
     *     'someCommandNamespace' => 'someDestinationExchange'
     * ]
     *
     * @return array
     */
    abstract protected function getMessageRouterConfiguration(): array;

    /**
     * Get aggregates list
     *
     * [
     *     0 => 'someAggregateNamespace',
     *     1 => 'someAggregateNamespace',
     *     ....
     * ]
     *
     *
     * @return array
     */
    abstract protected function getAggregatesList(): array;

    /**
     * Get sagas list
     *
     * [
     *     0 => 'someSagaNamespace',
     *     1 => 'someSagaNamespace',
     *     ....
     * ]
     *
     *
     * @return array
     */
    abstract protected function getSagasList(): array;

    /**
     * Get aggregates event storage
     *
     * @return EventStorageInterface
     */
    abstract protected function getAggregateEventStorage(): EventStorageInterface;

    /**
     * Get sagas event storage
     *
     * @return EventStorageInterface
     */
    abstract protected function getSagaEventStorage(): EventStorageInterface;

    /**
     * Get logger handlers
     *
     * @return \Monolog\Handler\HandlerInterface[]
     */
    protected function getLoggerHandlers(): array
    {
        return [
            new ColorizeStdOutHandler()
        ];
    }

    /**
     * Get application services
     *
     * @return ServiceInterface[]
     */
    abstract protected function getServices(): array;

    /**
     * Create application kernel
     *
     * @return AbstractKernel
     */
    abstract public function createKernel(): AbstractKernel;

    /**
     * Boot application
     *
     * @return EntryPoint
     */
    final public function boot(): EntryPoint
    {
        $messageBusBuilder = new MessageBusBuilder(
            new AnnotationsExtractor(
                $this->annotationsReader
            )
        );

        $this->initModules($messageBusBuilder);
        $this->initServices($messageBusBuilder);

        $this->messageBus = $messageBusBuilder->build();

        $kernel = $this->createKernel();
        $entryPoint = $this->createEntryPoint($kernel);

        return $entryPoint;
    }

    /**
     * Get modules list
     *
     * @return ModuleInterface[]
     */
    protected function getModules(): array
    {
        $modules = [
            new MessageErrorHandlerModule()
        ];

        if(true === \class_exists('Desperado\EventSourcing\Saga\AbstractSaga'))
        {
            $modules[] = new SagaModule(
                $this->getStorageManagersRegistry()->getSagaManagers(),
                new AnnotationsSagaConfigurationExtractor($this->annotationsReader)
            );
        }

        if(true === \class_exists('Symfony\Component\Validator'))
        {
            $modules[] = new MessageValidationModule();
        }

        return $modules;
    }

    /**
     * Get message bus
     *
     * @return MessageBusInterface
     */
    final protected function getMessageBus(): MessageBusInterface
    {
        return $this->messageBus;
    }

    /**
     * Get root directory path
     *
     * @return string
     */
    final protected function getRootDirectoryPath(): string
    {
        return $this->rootDirectoryPath;
    }

    /**
     * Get entry point name
     *
     * @return string
     */
    final protected function getEntryPointName(): string
    {
        return $this->entryPointName;
    }

    /**
     * Get application environment
     *
     * @return Environment
     */
    final protected function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Get annotations reader
     *
     * @return AnnotationsReaderInterface
     */
    final protected function getAnnotationsReader(): AnnotationsReaderInterface
    {
        return $this->annotationsReader;
    }

    /**
     * Get message serializer
     *
     * @return MessageSerializerInterface
     */
    final protected function getMessageSerializer(): MessageSerializerInterface
    {
        return $this->messageSerializer;
    }

    /**
     * Get stored message serializer
     *
     * @return MessageSerializerInterface
     */
    final protected function getStoredMessageSerializer(): MessageSerializerInterface
    {
        return $this->storedMessageSerializer;
    }

    /**
     * Get storage managers registry
     *
     * @return StorageManagerRegistry
     */
    final protected function getStorageManagersRegistry(): StorageManagerRegistry
    {
        if(null === $this->storageManagersRegistry)
        {
            $this->storageManagersRegistry = new StorageManagerRegistry();
        }

        return $this->storageManagersRegistry;
    }

    /**
     * Get message router
     *
     * @return MessageRouterInterface
     */
    final protected function getMessageRouter(): MessageRouterInterface
    {
        return $this->messageRouter;
    }

    /**
     * Get execution metrics collector
     *
     * @return MetricsCollectorInterface
     */
    final protected function getExecutionMetricsCollector(): MetricsCollectorInterface
    {
        return $this->executionMetricsCollector;
    }

    /**
     * Init message router
     *
     * @return void
     */
    private function initMessageRouter(): void
    {
        $this->messageRouter = new MessageRouter($this->getMessageRouterConfiguration());
    }

    /**
     * Create application entry point
     *
     * @param AbstractKernel $kernel
     *
     * @return EntryPoint
     */
    private function createEntryPoint(AbstractKernel $kernel): EntryPoint
    {
        return new EntryPoint(
            $this->entryPointName,
            $this->messageSerializer,
            $kernel
        );
    }

    /**
     * Init application modules
     *
     * @param MessageBusBuilder $messageBusBuilder
     *
     * @return void
     */
    private function initModules(MessageBusBuilder $messageBusBuilder): void
    {
        foreach($this->getModules() as $module)
        {
            if($module instanceof ModuleInterface)
            {
                $module->boot($messageBusBuilder);
            }
        }
    }

    /**
     * Init application services
     *
     * @param MessageBusBuilder $messageBusBuilder
     *
     * @return void
     *
     * @throws EntryPointException
     */
    private function initServices(MessageBusBuilder $messageBusBuilder): void
    {
        foreach($this->getServices() as $service)
        {
            if($service instanceof ServiceInterface)
            {
                $messageBusBuilder->applyService($service);
            }
            else
            {
                throw new EntryPointException(
                    \sprintf(
                        'Service must implement "%s" interface', ServiceInterface::class
                    )
                );
            }
        }
    }

    /**
     * Init DotEnv
     *
     * @return void
     *
     * @throws EntryPointException
     */
    private function initDotEnv(): void
    {
        try
        {
            if(
                '' !== (string) $this->environmentFilePath &&
                true === \file_exists($this->environmentFilePath) &&
                true === \is_readable($this->environmentFilePath)
            )
            {
                (new Dotenv())->load($this->environmentFilePath);
            }
            else
            {
                throw new \InvalidArgumentException(
                    \sprintf('.env file not found (specified path: %s)', $this->environmentFilePath)
                );
            }
        }
        catch(\Throwable $throwable)
        {
            throw new EntryPointException(
                \sprintf('Can\'t initialize DotEnv component with error "%s"', $throwable->getMessage()),
                $throwable->getCode(),
                $throwable
            );
        }
    }

    /**
     * Init application environment
     *
     * @return void
     */
    private function initEnvironment(): void
    {
        $environment = '' !== (string) \getenv('APP_ENVIRONMENT')
            ? (string) \getenv('APP_ENVIRONMENT')
            : Environment::ENVIRONMENT_SANDBOX;

        $this->environment = new Environment($environment);
    }

    /**
     * Init entry point name
     *
     * @return void
     */
    private function initEntryPoint(): void
    {
        $this->entryPointName = (string) \getenv('APP_ENTRY_POINT_NAME');

        if('' === $this->entryPointName)
        {
            throw new EntryPointException(
                'Entry point name must be specified (see APP_ENTRY_POINT_NAME environment variable)'
            );
        }
    }

    /**
     * Init logger registry
     *
     * @todo: channel configuration support
     *
     * @return void
     */
    private function initLoggerRegistry()
    {
        ApplicationLogger::setupEnvironment($this->environment);
        ApplicationLogger::setupEntryPointName($this->entryPointName);

        LoggerRegistry::setupHandlers($this->getLoggerHandlers());
    }

    /**
     * Init saga storage managers
     *
     * @return void
     *
     * @throws EntryPointException
     */
    private function initSagaStorage(): void
    {
        $eventStore = new EventStore(
            $this->getSagaEventStorage(),
            $this->storedMessageSerializer
        );

        $sagaRepository = new SagaRepository($eventStore);

        foreach($this->getSagasList() as $sagaNamespace)
        {
            if(true === \class_exists($sagaNamespace))
            {
                $this
                    ->getStorageManagersRegistry()
                    ->addSagaStorageManager(
                        $sagaNamespace,
                        new SagaStorageManager(
                            $sagaNamespace,
                            $sagaRepository
                        )
                    );
            }
            else
            {
                throw new EntryPointException(
                    \sprintf(
                        'Saga class "%s" not found', $sagaNamespace
                    )
                );
            }
        }
    }

    /**
     * Init aggregates storage managers
     *
     * @return void
     *
     * @throws EntryPointException
     */
    private function initAggregatesStorage(): void
    {
        $eventStore = new EventStore(
            $this->getAggregateEventStorage(),
            $this->storedMessageSerializer
        );

        $aggregateRepository = new AggregateRepository($eventStore);

        foreach($this->getAggregatesList() as $aggregateNamespace)
        {
            if(true === \class_exists($aggregateNamespace))
            {
                $this
                    ->getStorageManagersRegistry()
                    ->addAggregateStorageManager(
                        $aggregateNamespace,
                        new AggregateStorageManager(
                            $aggregateNamespace,
                            $aggregateRepository
                        )
                    );
            }
            else
            {
                throw new EntryPointException(
                    \sprintf(
                        'Aggregate class "%s" not found', $aggregateNamespace
                    )
                );
            }
        }
    }

}
