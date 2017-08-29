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

namespace Desperado\ConcurrencyFramework\Application;

use Desperado\ConcurrencyFramework\Application;
use Desperado\ConcurrencyFramework\Common;
use Desperado\ConcurrencyFramework\Domain;
use Desperado\ConcurrencyFramework\Infrastructure;
use Psr\Log\LoggerInterface;

/**
 * Base application class
 */
abstract class AbstractKernel implements Infrastructure\Application\KernelInterface
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
     * Aggregate/Saga storage manager
     *
     * @var Infrastructure\StorageManager\AbstractStorageManager[]
     */
    private $storageManagers = [];

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

        $this->initLogger();
        $this->initConfiguration();
        $this->initEnvironment();
        $this->initEntryPointName();
        $this->initMessagesRouter();
        $this->initMessageBus();
        $this->initEventSourcedStorage();
    }

    /**
     * @inheritdoc
     */
    public function handleMessage(
        Domain\Messages\MessageInterface $message,
        Domain\Context\ContextInterface $context
    ): void
    {
        /** @var Infrastructure\CQRS\Context\DeliveryContextInterface $context */

        $loggerContext = new Application\Context\Variables\ContextLogger();
        $contextEntryPoint = new Application\Context\Variables\ContextEntryPoint(
            $this->entryPointName, $this->environment
        );

        $contextMessages = new Application\Context\Variables\ContextMessages(
            $context, $this->messagesRouter, $loggerContext
        );

        $contextStorage = new Application\Context\Variables\ContextStorage(
            $this->storageManagers
        );

        $kernelContext = new Application\Context\KernelContext(
            $contextEntryPoint,
            $contextMessages,
            $contextStorage,
            $loggerContext
        );

        $this->messageBus->handle($message, $kernelContext);
    }

    /**
     * Get modules
     *
     * @return Domain\Module\AbstractModule[]
     */
    abstract protected function getModules(): array;

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
     * Init message bus
     *
     * @return void
     */
    protected function initMessageBus(): void
    {
        $messageBusBuilder = new Infrastructure\CQRS\MessageBus\MessageBusBuilder();

        foreach($this->getModules() as $module)
        {
            if($module instanceof Domain\Module\AbstractModule)
            {
                $module->boot($messageBusBuilder);
            }
            else
            {
                $this->logger->critical(
                    \sprintf('Module must extends %s', Domain\Module\AbstractModule::class)
                );
            }
        }

        $this->messageBus = $messageBusBuilder->build();
    }

    /**
     * Init event sourced entries storage
     *
     * @return void
     */
    protected function initEventSourcedStorage()
    {
        $parameters = new Domain\ParameterBag($this->configuration->get('eventSourced', []));

        /** Init saga storages */
        if(true === $parameters->has('saga'))
        {
            $sagaConfig = new Domain\ParameterBag($parameters->get('saga', []));

            $sagaStorage = Infrastructure\EventSourcing\Storage\StorageFactory::create(
                $sagaConfig->getAsString('storageDSN')
            );
            $sagaRepository = new Infrastructure\EventSourcing\Repository\SagaRepository(
                new Infrastructure\EventSourcing\EventStore\EventStore(
                    $sagaStorage, $this->messageSerializer
                )
            );

            foreach($sagaConfig->get('list', []) as $saga)
            {
                $this->storageManagers[$saga] = new Infrastructure\StorageManager\SagaStorageManager(
                    $saga, $sagaRepository
                );
            }
        }

        /** Init aggregate storage */
        if(true === $parameters->has('aggregate'))
        {
            $aggregateConfig = new Domain\ParameterBag($parameters->get('aggregate', []));

            $aggregateStorage = Infrastructure\EventSourcing\Storage\StorageFactory::create(
                $aggregateConfig->getAsString('storageDSN')
            );
            $aggregateRepository = new Infrastructure\EventSourcing\Repository\AggregateRepository(
                new Infrastructure\EventSourcing\EventStore\EventStore(
                    $aggregateStorage, $this->messageSerializer
                )
            );

            foreach($aggregateConfig->get('list', []) as $aggregate)
            {
                $this->storageManagers[$aggregate] = new Infrastructure\StorageManager\AggregateStorageManager(
                    $aggregate, $aggregateRepository
                );
            }
        }
    }

    /**
     * Init messages router
     *
     * @return void
     */
    protected function initMessagesRouter(): void
    {
        $appExchanges = new Domain\ParameterBag($this->configuration->get('applicationExchanges', []));
        $responseRoutes = $this->configuration->get('responseMessageRoutes', []);

        $this->messagesRouter = new Infrastructure\MessageRouter\MessageRouter(
            $appExchanges->getAsString('commands', $this->entryPointName),
            $appExchanges->getAsString(
                'events', \sprintf('%s.events', $this->entryPointName)
            ),
            true === \is_array($responseRoutes) ? $responseRoutes : []
        );
    }

    /**
     * Init entry point
     *
     * @return void
     *
     * @throws Application\Exceptions\EmptyEntryPointNameException
     */
    protected function initEntryPointName(): void
    {
        $this->entryPointName = $this->configuration->getAsString('entryPoint');

        if('' === $this->entryPointName)
        {
            throw new Application\Exceptions\EmptyEntryPointNameException(
                'Entry point name must be specified ("APP_ENTRY_POINT_NAME" variable)'
            );
        }
    }

    /**
     * Init environment
     *
     * @return void
     */
    protected function initEnvironment()
    {
        $this->environment = new Domain\Environment\Environment(
            $this->configuration->getAsString(
                'environment',
                Domain\Environment\Environment::ENVIRONMENT_SANDBOX
            )
        );
    }

    /**
     * Parse configuration
     *
     * @return void
     */
    protected function initConfiguration(): void
    {
        $configurationFilePath = $this->rootDirectoryPath . '/app/configs/parameters.yaml';

        $configurationLoader = new Application\Configuration\ConfigurationLoader(
            $this->environmentFilePath, $configurationFilePath
        );

        $this->configuration = $configurationLoader->loadParameters();
    }

    /**
     * Init loggers
     *
     * @todo: configuration support
     *
     * @return void
     */
    protected function initLogger(): void
    {
        $this->logger = Common\Logger\LoggerRegistry::getLogger();
    }
}
