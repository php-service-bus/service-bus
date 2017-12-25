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

use Desperado\CQRS\MessageBus;
use Desperado\Domain as DesperadoDomain;
use Desperado\Framework as DesperadoFramework;
use Desperado\Infrastructure\Bridge\Logger\LoggerRegistry;
use Symfony\Component\DependencyInjection;
use Symfony\Component\Dotenv\Dotenv;


/**
 * Base application bootstrap
 */
abstract class AbstractBootstrap
{
    /**
     * DI container
     *
     * @var DependencyInjection\ContainerBuilder
     */
    private $container;

    /**
     * @param string $rootDirectoryPath
     * @param string $environmentFilePath
     *
     * @throws DesperadoFramework\Exceptions\EntryPointException
     */
    public function __construct(string $rootDirectoryPath, string $environmentFilePath)
    {
        $this->initDotEnv($environmentFilePath);

        $environment = '' !== (string) \getenv('APP_ENVIRONMENT')
            ? (string) \getenv('APP_ENVIRONMENT')
            : DesperadoDomain\Environment\Environment::ENVIRONMENT_SANDBOX;

        $this->buildContainer(
            \rtrim($rootDirectoryPath, '/'),
            \strtolower($environment),
            (string) \getenv('APP_ENTRY_POINT_NAME')
        );
    }

    /**
     * Boot application
     *
     * @return EntryPoint
     */
    final public function boot()
    {
        $this->configureSagas();
        $this->configureAggregates();
        $this->configureServices();

        /** @var MessageBus\MessageBus $messageBus */
        $messageBus = $this->getContainer()->get('kernel.cqrs.message_bus_builder')->build();

        $messageProcessor = new DesperadoFramework\MessageProcessor(
            $messageBus,
            $this->getContainer()->get('kernel.event_sourcing.service'),
            $this->getContainer()->get('kernel.sagas.service')
        );

        $kernel = $this->createKernel($messageProcessor, $this->container);

        return new EntryPoint(
            $this->getContainer()->getParameter('kernel.entry_point'),
            $this->getContainer()->get('kernel.serializer.messages'),
            $kernel
        );
    }

    /**
     * @return DependencyInjection\ContainerInterface
     */
    final public function getContainer(): DependencyInjection\ContainerInterface
    {
        return $this->container;
    }

    /**
     * Create application kernel
     *
     * @param DesperadoFramework\MessageProcessor    $messageProcessor
     * @param DependencyInjection\ContainerInterface $container
     *
     * @return AbstractKernel
     */
    abstract protected function createKernel(
        DesperadoFramework\MessageProcessor $messageProcessor,
        DependencyInjection\ContainerInterface $container
    ): AbstractKernel;

    /**
     * Get aggregates event storage
     *
     * @return DesperadoDomain\EventStore\EventStorageInterface
     */
    abstract protected function getAggregateEventStorage(): DesperadoDomain\EventStore\EventStorageInterface;

    /**
     * Get sagas storage
     *
     * @return DesperadoDomain\SagaStore\SagaStorageInterface
     */
    abstract protected function getSagaStorage(): DesperadoDomain\SagaStore\SagaStorageInterface;

    /**
     * Get aggregates list
     *
     * [
     *     'someAggregateNamespace' => 'someAggregateIdentityClassNamespace',
     *     'someAggregateNamespace' => 'someAggregateIdentityClassNamespace',
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
     * Get application services
     *
     * @return DesperadoDomain\CQRS\ServiceInterface[]
     */
    abstract protected function getServices(): array;

    /**
     * Get dependency injection  pass collection
     *
     * @return DependencyInjection\Compiler\CompilerPassInterface[]
     */
    protected function getCompilerPassCollection(): array
    {
        return [];
    }

    /**
     * Get dependency injection extensions
     *
     * @return DependencyInjection\Extension\Extension[]
     */
    protected function getClientExtensionsCollection(): array
    {
        return [];
    }

    /**
     * Init DI container
     *
     * @todo: dump it
     *
     * @param string $rootDirectoryPath
     * @param string $environment
     * @param string $entryPointName
     *
     * @return void
     *
     * @throws DesperadoFramework\Exceptions\EntryPointException
     */
    private function buildContainer(string $rootDirectoryPath, string $environment, string $entryPointName): void
    {
        BootstrapGuard::guardEnvironment($environment);
        BootstrapGuard::guardEntryPointName($entryPointName);
        BootstrapGuard::guardPath(
            $rootDirectoryPath,
            'Incorrect path of root directory specified'
        );

        try
        {
            $this->container = new DependencyInjection\ContainerBuilder(
                new DependencyInjection\ParameterBag\ParameterBag([
                    'kernel.root_dir'    => $rootDirectoryPath,
                    'kernel.env'         => $environment,
                    'kernel.entry_point' => $entryPointName
                ])
            );

            $this->getContainer()->set('kernel.logger', LoggerRegistry::getLogger($entryPointName));
            $this->getContainer()->set('kernel.event_sourcing.storage', $this->getAggregateEventStorage());
            $this->getContainer()->set('kernel.sagas.storage', $this->getSagaStorage());

            $this->applyContainerExtensions();
            $this->applyContainerCompilerPass();


        }
        catch(\Throwable $throwable)
        {
            throw new DesperadoFramework\Exceptions\EntryPointException(
                \sprintf('Can\'t initialize DI container with error "%s"', $throwable->getMessage()),
                $throwable->getCode(),
                $throwable
            );
        }
    }

    /**
     * Configure sagas
     *
     * @return void
     */
    private function configureSagas(): void
    {
        foreach($this->getSagasList() as $namespace => $identityNamespace)
        {
            $this->container
                ->get('kernel.sagas.service')
                ->configure(
                    $this->container->get('kernel.cqrs.message_bus_builder'),
                    $namespace
                );
        }
    }

    /**
     * Configure application aggregates
     *
     * @return void
     */
    private function configureAggregates(): void
    {
        foreach($this->getAggregatesList() as $aggregateNamespace => $aggregateIdentityNamespace)
        {
            $this->container
                ->get('kernel.event_sourcing.service')
                ->configure($aggregateNamespace, $aggregateIdentityNamespace);
        }
    }

    /**
     * Configure services
     *
     * @return void
     */
    private function configureServices(): void
    {
        foreach($this->getServices() as $service)
        {
            $this->container->get('kernel.cqrs.message_bus_builder')->applyService($service);
        }
    }

    /**
     * Apply extensions
     *
     * @return void
     */
    private function applyContainerExtensions(): void
    {
        $extensions = \array_merge(
            $this->getClientExtensionsCollection(),
            [new DesperadoFramework\DependencyInjection\FrameworkExtension()]
        );

        foreach($extensions as $extension)
        {
            /** @var DependencyInjection\Extension\Extension $extension */
            $extension->load([], $this->container);
        }
    }

    /**
     * Apply compiler pass
     *
     * @return void
     */
    private function applyContainerCompilerPass(): void
    {
        $compilerPassCollection = \array_merge(
            $this->getCompilerPassCollection(),
            [
                new DesperadoFramework\DependencyInjection\Compiler\LoggerCompilerPass(),
                new DesperadoFramework\DependencyInjection\Compiler\ModulesCompilerPass()
            ]
        );

        foreach($compilerPassCollection as $compilerPass)
        {
            $this->container->addCompilerPass($compilerPass);
        }
    }

    /**
     * Init DotEnv
     *
     * @param string $environmentFilePath
     *
     * @return void
     *
     * @throws DesperadoFramework\Exceptions\EntryPointException
     */
    private function initDotEnv(string $environmentFilePath): void
    {
        BootstrapGuard::guardPath(
            $environmentFilePath,
            \sprintf('.env file not found (specified path: %s)', $environmentFilePath)
        );

        try
        {
            (new Dotenv())->load($environmentFilePath);
        }
        catch(\Throwable $throwable)
        {
            throw new DesperadoFramework\Exceptions\EntryPointException(
                \sprintf('Can\'t initialize DotEnv component with error "%s"', $throwable->getMessage()),
                $throwable->getCode(),
                $throwable
            );
        }
    }
}
