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

namespace Desperado\Framework\Application;

use Desperado\Framework\Application;
use Desperado\Framework\Common;
use Desperado\Framework\Domain;
use Desperado\Framework\Infrastructure;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Psr\Log\LoggerInterface;
use React\Promise\Deferred;

/**
 * Base application class
 */
abstract class AbstractKernel implements Domain\Application\KernelInterface
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
     * Message serializer
     *
     * @var Domain\Serializer\MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * Configuration parameters
     *
     * @var Domain\ParameterBag
     */
    private $configuration;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Application environment
     *
     * @var Domain\Environment\Environment
     */
    private $environment;

    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Messages router
     *
     * @var Infrastructure\MessageRouter\MessageRouter
     */
    private $messagesRouter;

    /**
     * Message bus
     *
     * @var Domain\MessageBus\MessageBusInterface
     */
    private $messageBus;

    /**
     * Storage manager registry
     *
     * @var Application\Storage\StorageManagerRegistry
     */
    private $storageManagersRegistry;

    /**
     * Annotations reader
     *
     * @var Infrastructure\Bridge\Annotation\AnnotationReader
     */
    private $annotationsReader;

    /**
     * DBAL connections
     *
     * @var Connection[]
     */
    private $dbalConnections = [];

    /**
     * @param string $rootDirectoryPath
     * @param string $environmentFilePath
     */
    final public function __construct(
        string $rootDirectoryPath,
        string $environmentFilePath
    )
    {
        $this->rootDirectoryPath = \rtrim($rootDirectoryPath, '/');
        $this->environmentFilePath = $environmentFilePath;
        $this->annotationsReader = new Infrastructure\Bridge\Annotation\AnnotationReader();

        $this->logger = $this->initLogger();
        $this->configuration = $this->initConfiguration();
        $this->environment = $this->initEnvironment();
        $this->entryPointName = $this->initEntryPointName();
        $this->messageSerializer = $this->initMessageSerializer();
        $this->messagesRouter = $this->initMessagesRouter();
        $this->dbalConnections = $this->initDBALConnections();
        $this->storageManagersRegistry = $this->initStorageManagers();
        $this->messageBus = $this->initMessageBus();
    }

    /**
     * @inheritdoc
     */
    final public function handleMessage(
        Domain\Messages\MessageInterface $message,
        Domain\Context\ContextInterface $context
    ): void
    {
        /** @var Infrastructure\CQRS\Context\DeliveryContextInterface $context */

        $executionContext = $this->createExecutionContext($context);

        $deferred = new Deferred();
        $deferred
            ->promise()
            ->then(
            /** Handle message */
                function(Domain\Messages\MessageInterface $message) use ($executionContext)
                {
                    try
                    {
                        $this->messageBus->handle($message, $executionContext);
                    }
                    catch(\Throwable $throwable)
                    {
                        $executionContext->logThrowable($throwable);
                    }

                    return $executionContext;
                }
            )
            ->then(
            /** Save aggregates, publish events/send commands */
                function(Application\Context\KernelContext $executionContext)
                {
                    try
                    {
                        $persistProcessor = new Application\Storage\FlushStorageManagersProcessor(
                            $this->storageManagersRegistry,
                            $this->logger
                        );

                        $persistProcessor->process($executionContext);
                    }
                    catch(\Throwable $throwable)
                    {
                        $executionContext->logThrowable($throwable);
                    }

                    return $executionContext;
                }
            )
            ->then(null, $executionContext->getLogThrowableCallable());

        $deferred->resolve($message);
    }

    /**
     * Create message execution context
     *
     * @param Infrastructure\CQRS\Context\DeliveryContextInterface $originalContext
     *
     * @return Context\KernelContext
     */
    protected function createExecutionContext(
        Infrastructure\CQRS\Context\DeliveryContextInterface $originalContext
    ): Application\Context\KernelContext
    {
        $loggerContext = new Application\Context\Variables\ContextLogger();
        $contextEntryPoint = new Application\Context\Variables\ContextEntryPoint(
            $this->entryPointName, $this->environment
        );
        $contextMessages = new Application\Context\Variables\ContextMessages(
            $originalContext, $this->messagesRouter
        );
        $contextStorage = new Application\Context\Variables\ContextStorage(
            $this->storageManagersRegistry
        );

        return new Application\Context\KernelContext(
            $contextEntryPoint,
            $contextMessages,
            $contextStorage,
            $loggerContext
        );
    }

    /**
     * Get modules
     *
     * @return Application\Module\AbstractModule[]
     */
    abstract protected function getModules(): array;

    /**
     * @return Domain\Behavior\BehaviorInterface[]
     */
    protected function getBehaviors(): array
    {
        return [
            new  Infrastructure\CQRS\Behavior\ValidationErrorBehavior(),
            new Infrastructure\CQRS\Behavior\HandleErrorBehavior()
        ];
    }

    /**
     * Get source directory path
     *
     * @return string
     */
    protected function getSourceDirectoryPath(): string
    {
        return \sprintf('%s/src', \rtrim($this->rootDirectoryPath, '/'));
    }

    /**
     * Application init
     * Custom application initialization
     *
     * @return void
     */
    protected function init(): void
    {

    }

    /**
     * Init DBAL connections
     *
     * @return array
     */
    protected function initDBALConnections(): array
    {
        $connections = [];

        foreach($this->getDBALConnectionsConfig() as $alias => $connectionDSN)
        {
            if('' === (string) $alias || '' === (string) $connectionDSN)
            {
                throw new \LogicException(
                    'The alias of the connection name and the DSN string must be specified'
                );
            }
            $config = Infrastructure\EventSourcing\Storage\Configuration\StorageConfigurationConfig::fromDSN(
                $connectionDSN
            );

            $connections[$alias] = DriverManager::getConnection([
                'dbname'   => $config->getDatabase(),
                'user'     => $config->getAuth()->getUsername(),
                'password' => $config->getAuth()->getPassword(),
                'host'     => $config->getHost()->getHost(),
                'driver'   => 'doctrinePgSql' === $config->getDriver() ? 'pdo_pgsql' : 'pdo_mysql'
            ],
                new Configuration()
            );
        }

        return $connections;
    }

    /**
     * Init messages serializer
     *
     * @return Domain\Serializer\MessageSerializerInterface
     */
    protected function initMessageSerializer(): Domain\Serializer\MessageSerializerInterface
    {
        return new Application\Serializer\MessageSerializer(
            new Infrastructure\Bridge\Serializer\SymfonySerializer()
        );
    }

    /**
     * Init message bus
     *
     * @return Domain\MessageBus\MessageBusInterface
     */
    protected function initMessageBus(): Domain\MessageBus\MessageBusInterface
    {
        $messageBusBuilder = new Infrastructure\CQRS\MessageBus\MessageBusBuilder();
        $modules = $this->getModules();

        $sagasConfig = $this->getSagasConfiguration();
        $sagas = $sagasConfig->get('list', []);

        if(true === \is_array($sagas) && 0 !== \count($sagas))
        {
            $modules[] = new Application\Module\SagasModule($sagas, $this->storageManagersRegistry, $this->logger);
        }

        foreach($modules as $module)
        {
            if($module instanceof Application\Module\AbstractModule)
            {
                $module->boot($messageBusBuilder, $this->annotationsReader);

                $this->logger->debug(
                    \sprintf('Module "%s" successful booted', \get_class($module))
                );
            }
            else
            {
                $this->logger->critical(
                    \sprintf('Module must extends %s', Application\Module\AbstractModule::class)
                );
            }
        }

        foreach($this->getBehaviors() as $behavior)
        {
            $messageBusBuilder->addBehavior($behavior);

            $this->logger->debug(
                \sprintf('Behavior "%s" successful enabled', \get_class($behavior))
            );
        }

        $this->logger->debug('The message bus has been successfully configured');

        return $messageBusBuilder->build();
    }

    /**
     * Init storage managers
     *
     * @return Application\Storage\StorageManagerRegistry
     */
    protected function initStorageManagers(): Application\Storage\StorageManagerRegistry
    {
        $registry = new Application\Storage\StorageManagerRegistry();

        $ormConfig = new Domain\ParameterBag((array) $this->getConfiguration()->get('orm', []));

        $factory = new Application\Storage\StorageManagerFactory(
            $registry,
            $this->logger,
            new Application\Serializer\StoreEventPayloadSerializer($this->messageSerializer),
            $this->environment,
            $this->getSourceDirectoryPath()
        );

        $factory->appendEventSourced(
            Application\Storage\StorageManagerFactory::TYPE_SAGAS,
            $this->getSagasConfiguration()
        );

        $factory->appendEventSourced(
            Application\Storage\StorageManagerFactory::TYPE_AGGREGATES,
            $this->getAggregatesConfiguration()
        );

        $factory->appendEntities($ormConfig->all(), $this->dbalConnections);

        $this->logger->debug('Storage managers successfully configured');

        return $registry;
    }

    /**
     * Init messages router
     *
     * @return Domain\MessageRouter\MessageRouterInterface
     */
    protected function initMessagesRouter(): Domain\MessageRouter\MessageRouterInterface
    {
        $responseRoutes = $this->configuration->get('responseMessageRoutes', []);

        $router = new Infrastructure\MessageRouter\MessageRouter(
            true === \is_array($responseRoutes)
                ? $responseRoutes
                : [],
            $this->logger
        );

        $this->logger->debug('Message router successfully configured');

        return $router;
    }

    /**
     * Init entry point
     *
     * @return string
     *
     * @throws Application\Exceptions\EmptyEntryPointNameException
     */
    protected function initEntryPointName(): string
    {
        $entryPointName = $this->configuration->getAsString('entryPoint');

        if('' !== $entryPointName)
        {
            return $entryPointName;
        }

        throw new Application\Exceptions\EmptyEntryPointNameException(
            'Entry point name must be specified ("APP_ENTRY_POINT_NAME" variable)'
        );
    }

    /**
     * Init environment
     *
     * @return Domain\Environment\Environment
     */
    protected function initEnvironment(): Domain\Environment\Environment
    {
        return new Domain\Environment\Environment(
            $this->configuration->getAsString(
                'environment',
                Domain\Environment\Environment::ENVIRONMENT_SANDBOX
            )
        );
    }

    /**
     * Load application configuration
     *
     * @return Domain\ParameterBag
     */
    protected function initConfiguration(): Domain\ParameterBag
    {
        $configurationFilePath = $this->rootDirectoryPath . '/app/configs/parameters.yaml';

        $configurationLoader = new Application\Configuration\ConfigurationLoader(
            $this->environmentFilePath, $configurationFilePath
        );

        return $configurationLoader->loadParameters();
    }

    /**
     * Init logger
     *
     * @todo: configuration support
     *
     * @return LoggerInterface
     */
    protected function initLogger(): LoggerInterface
    {
        return Infrastructure\Bridge\Logger\LoggerRegistry::getLogger();
    }

    /**
     * Get root directory path
     *
     * @return string
     */
    protected function getRootDirectoryPath(): string
    {
        return $this->rootDirectoryPath;
    }

    /**
     * Get environment
     *
     * @return string
     */
    protected function getEnvironmentFilePath(): string
    {
        return $this->environmentFilePath;
    }

    /**
     * Get message serializer
     *
     * @return Domain\Serializer\MessageSerializerInterface
     */
    public function getMessageSerializer(): Domain\Serializer\MessageSerializerInterface
    {
        return $this->messageSerializer;
    }

    /**
     * Get configuration parameters
     *
     * @return Domain\ParameterBag
     */
    protected function getConfiguration(): Domain\ParameterBag
    {
        return $this->configuration;
    }

    /**
     * Get logger
     *
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get application environment
     *
     * @return Domain\Environment\Environment
     */
    protected function getEnvironment(): Domain\Environment\Environment
    {
        return $this->environment;
    }

    /**
     * Get entry point name
     *
     * @return string
     */
    protected function getEntryPointName(): string
    {
        return $this->entryPointName;
    }

    /**
     * Get message router
     *
     * @return Infrastructure\MessageRouter\MessageRouter
     */
    protected function getMessagesRouter(): Infrastructure\MessageRouter\MessageRouter
    {
        return $this->messagesRouter;
    }

    /**
     * Get messages bus
     *
     * @return Domain\MessageBus\MessageBusInterface
     */
    protected function getMessageBus(): Domain\MessageBus\MessageBusInterface
    {
        return $this->messageBus;
    }

    /**
     * Get storage manager registry
     *
     * @return Storage\StorageManagerRegistry
     */
    protected function getStorageManagersRegistry(): Storage\StorageManagerRegistry
    {
        return $this->storageManagersRegistry;
    }

    /**
     * Get DBAL connections config
     *
     * @return Domain\ParameterBag
     */
    private function getDBALConnectionsConfig(): Domain\ParameterBag
    {
        $dbalSection = new Domain\ParameterBag((array) $this->getConfiguration()->get('dbal', []));

        return new Domain\ParameterBag($dbalSection->get('connections', []));
    }

    /**
     * Get sagas config
     *
     * @return Domain\ParameterBag
     */
    private function getSagasConfiguration(): Domain\ParameterBag
    {
        return new Domain\ParameterBag((array) $this->getEventSourcedConfiguration()->get('saga', []));
    }

    /**
     * Get aggregates config
     *
     * @return Domain\ParameterBag
     */
    private function getAggregatesConfiguration(): Domain\ParameterBag
    {
        return new Domain\ParameterBag((array) $this->getEventSourcedConfiguration()->get('aggregate', []));
    }

    /**
     * Get event sourced entries config
     *
     * @return Domain\ParameterBag
     */
    private function getEventSourcedConfiguration(): Domain\ParameterBag
    {
        return new Domain\ParameterBag((array) $this->configuration->get('eventSourced', []));
    }
}
