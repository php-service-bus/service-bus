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

namespace Desperado\ConcurrencyFramework\Application;

use Desperado\ConcurrencyFramework\Application;
use Desperado\ConcurrencyFramework\Common;
use Desperado\ConcurrencyFramework\Domain;
use Desperado\ConcurrencyFramework\Infrastructure;
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
        $this->storageManagersRegistry = $this->initEventSourcedStorage();
        $this->messageBus = $this->initMessageBus();
    }

    /**
     * @inheritdoc
     */
    public function handleMessage(
        Domain\Messages\MessageInterface $message,
        Domain\Context\ContextInterface $context
    ): void
    {
        $environment = $this->environment;
        $entryPoint = $this->entryPointName;
        $router = $this->messagesRouter;
        $logger = $this->logger;
        $storageManagersRegistry = $this->storageManagersRegistry;
        $messageBus = $this->messageBus;

        $logFailCallable = function(\Throwable $throwable) use ($logger)
        {
            $logger->error(Common\Formatter\ThrowableFormatter::toString($throwable));
        };

        $task = new \stdClass();
        $task->message = $message;
        $task->originalContext = $context;

        $deferred = new Deferred();
        $deferred
            ->promise()
            ->then(
                function(\stdClass $task) use ($entryPoint, $environment, $router, $storageManagersRegistry, $messageBus)
                {
                    $loggerContext = new Application\Context\Variables\ContextLogger();
                    $contextEntryPoint = new Application\Context\Variables\ContextEntryPoint($entryPoint, $environment);
                    $contextMessages = new Application\Context\Variables\ContextMessages($task->originalContext, $router);
                    $contextStorage = new Application\Context\Variables\ContextStorage($storageManagersRegistry);

                    $kernelContext = new Application\Context\KernelContext(
                        $contextEntryPoint,
                        $contextMessages,
                        $contextStorage,
                        $loggerContext
                    );

                    $messageBus->handle($task->message, $kernelContext);

                    return $kernelContext;
                },
                $logFailCallable
            )
            ->then(
                function(Application\Context\KernelContext $context) use ($storageManagersRegistry)
                {
                    foreach($storageManagersRegistry as $storageManager)
                    {
                        $storageManager->commit($context);
                    }
                },
                $logFailCallable
            );

        $deferred->resolve($task);
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
            new Infrastructure\CQRS\Behavior\HandleErrorBehavior()
        ];
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
        }

        return $messageBusBuilder->build();
    }

    /**
     * Init event sourced entries storage
     *
     * @return Application\Storage\StorageManagerRegistry
     */
    protected function initEventSourcedStorage(): Application\Storage\StorageManagerRegistry
    {
        $registry = new Application\Storage\StorageManagerRegistry();

        $sagasConfig = $this->getSagasConfiguration();
        $aggregatesConfig = $this->getAggregatesConfiguration();

        Application\Storage\EventSourcedManagerFactory::sagas($sagasConfig, $this->messageSerializer)
            ->append($registry);

        Application\Storage\EventSourcedManagerFactory::aggregates($aggregatesConfig, $this->messageSerializer)
            ->append($registry);

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

        return new Infrastructure\MessageRouter\MessageRouter(
            true === \is_array($responseRoutes) ? $responseRoutes : []
        );
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
