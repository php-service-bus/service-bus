<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Bootstrap;

use Desperado\Domain\ParameterBag;
use Desperado\Infrastructure\Bridge\Logger\LoggerRegistry;
use Desperado\ServiceBus\Application\Bootstrap\Exceptions as BootstrapExceptions;
use Desperado\ServiceBus\DependencyInjection as ServiceBusDependencyInjection;
use Desperado\ServiceBus\Application\EntryPoint\EntryPoint;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Config;
use Symfony\Component\DependencyInjection as SymfonyDependencyInjection;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator;

/**
 * Base class initial initialization of the application
 */
abstract class AbstractBootstrap
{
    /**
     * Absolute path to the root of the application
     *
     * @var string
     */
    private $rootDirectoryPath;

    /**
     * Absolute path to the cache directory
     *
     * @var string
     */
    private $cacheDirectoryPath;

    /**
     * Absolute path to the ".env" configuration file
     *
     * @var string
     */
    private $environmentFilePath;

    /**
     * Service bus configuration
     *
     * @var Configuration
     */
    private $configuration;

    /**
     * Dependency injection container
     *
     * @var SymfonyDependencyInjection\Container
     */
    private $container;

    /**
     * Initializing components
     *
     * @param string $rootDirectoryPath   Absolute path to the root of the application
     * @param string $environmentFilePath Absolute path to the ".env" configuration file
     * @param string $cacheDirectoryPath  Absolute path to the cache directory
     *
     * @return self
     *
     * @throws BootstrapExceptions\IncorrectRootDirectoryPathException
     * @throws BootstrapExceptions\IncorrectDotEnvFilePathException
     * @throws BootstrapExceptions\IncorrectCacheDirectoryFilePathException
     * @throws BootstrapExceptions\ServiceBusConfigurationException
     * @throws \Exception
     */
    public static function boot(
        string $rootDirectoryPath,
        string $cacheDirectoryPath,
        string $environmentFilePath
    ): self
    {
        $startTimer = \microtime(true);

        $self = new static($rootDirectoryPath, $cacheDirectoryPath, $environmentFilePath);

        $self->loadConfiguration();
        $self->initializeContainer();

        $self->init();

        LoggerRegistry::getLogger('bootstrap')
            ->info(
                \sprintf('Application initialization time: %g', \microtime(true) - $startTimer)
            );

        return $self;
    }

    /**
     * Get application entry point
     *
     * @return EntryPoint
     *
     * @throws \Exception
     */
    final public function getEntryPoint(): EntryPoint
    {
        /** @var EntryPoint $entryPoint */
        $entryPoint = $this->getContainer()->get('service_bus.entry_point');

        return $entryPoint;
    }

    /**
     * Get dependency injection container
     *
     * @return SymfonyDependencyInjection\Container
     */
    final public function getContainer(): SymfonyDependencyInjection\Container
    {
        return $this->container;
    }

    /**
     * Get customer-configurable services
     *
     * @return BootstrapServicesDefinitions
     */
    abstract protected function getBootstrapServicesDefinitions(): BootstrapServicesDefinitions;

    /**
     * Get customer-configurable container parameters
     *
     * @return BootstrapContainerConfiguration
     */
    abstract protected function getBootstrapContainerConfiguration(): BootstrapContainerConfiguration;

    /**
     * Custom component initialization (container already compiled)
     *
     * @return void
     */
    protected function init(): void
    {

    }

    /**
     * Load configuration
     *
     * @return void
     *
     * @throws BootstrapExceptions\ServiceBusConfigurationException
     */
    private function loadConfiguration(): void
    {
        $validator = (new Validator\ValidatorBuilder())
            ->enableAnnotationMapping()
            ->getValidator();

        $this->configuration = Configuration::loadDotEnv($this->environmentFilePath);

        $violations = $validator->validate($this->configuration);

        foreach($violations as $violation)
        {
            /** @var Validator\ConstraintViolationInterface $violation */

            throw new BootstrapExceptions\ServiceBusConfigurationException($violation->getMessage());
        }
    }

    /**
     * Initialize dependency injection container
     *
     * @return void
     *
     * @throws BootstrapExceptions\ServiceBusConfigurationException
     */
    private function initializeContainer(): void
    {
        try
        {
            $containerClassName = \sprintf(
                'serviceBus%sProjectContainer',
                \ucfirst((string) $this->configuration->getEnvironment())
            );

            $containerClassPath = \sprintf('%s/%s.php', $this->cacheDirectoryPath, $containerClassName);

            $configCache = new Config\ConfigCache(
                \sprintf('%s/%s.php', $this->cacheDirectoryPath, $containerClassName),
                $this->configuration->getEnvironment()->isDebug()
            );

            if(false === $configCache->isFresh() || true === $this->configuration->getEnvironment()->isDebug())
            {
                $container = $this->buildContainer();
                $container->setParameter(
                    'service_bus.services_relations',
                    $this->extractServicesClassRelation($container)
                );

                $container->compile();
                $this->dumpContainer($configCache, $container, $containerClassName);
            }

            /** @noinspection PhpIncludeInspection */
            include $containerClassPath;

            $this->container = new $containerClassName();
        }
        catch(\Throwable $throwable)
        {
            throw new BootstrapExceptions\ServiceBusConfigurationException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * Build dependency injection container
     *
     * @return SymfonyDependencyInjection\ContainerBuilder
     */
    private function buildContainer(): SymfonyDependencyInjection\ContainerBuilder
    {
        $bootstrapContainerConfiguration = $this->getBootstrapContainerConfiguration();
        $bootstrapServicesDefinitions = $this->getBootstrapServicesDefinitions();

        $configData = $this->configuration->toArray();

        $applicationCompilerPass = [
            'logger_channel'    => new ServiceBusDependencyInjection\Compiler\LoggerChannelsCompilerPass(),
            'modules'           => new ServiceBusDependencyInjection\Compiler\ModulesCompilerPass(),
            'scheduler_storage' => new ServiceBusDependencyInjection\Compiler\SchedulerCompilerPass(
                $bootstrapServicesDefinitions->getSchedulerStorageKey()
            ),
            'saga_storage'      => new ServiceBusDependencyInjection\Compiler\SagaStorageCompilerPass(
                $bootstrapServicesDefinitions->getSagaStorageKey()
            ),
            'entry_point'       => new ServiceBusDependencyInjection\Compiler\EntryPointCompilerPass(
                $bootstrapServicesDefinitions->getMessageTransportKey(),
                $bootstrapServicesDefinitions->getKernelKey(),
                $bootstrapServicesDefinitions->getApplicationContextKey()
            ),
            'event_listeners'   => new RegisterListenersPass(
                'service_bus.event_dispatcher',
                'service_bus.event_listener',
                'service_bus.event_subscriber'
            ),
            'services'          => new ServiceBusDependencyInjection\Compiler\ServicesCompilerPass()
        ];

        $containerParameters = new SymfonyDependencyInjection\ParameterBag\ParameterBag([
            'service_bus.entry_point'  => $this->configuration->getEntryPointName(),
            'service_bus.root_dir'     => $this->rootDirectoryPath,
            'service_bus.cache_dir'    => $this->cacheDirectoryPath,
            'service_bus.is_debug_env' => $this->configuration->getEnvironment()->isDebug()
        ]);
        $containerExtensions = new ParameterBag();
        $containerCompilerPass = new ParameterBag();

        $containerCompilerPass->add($bootstrapContainerConfiguration->getCompilerPassCollection());
        $containerCompilerPass->add($applicationCompilerPass);

        $containerExtensions->add($bootstrapContainerConfiguration->getExtensionsCollection());
        $containerExtensions->add(['service_bus' => new ServiceBusDependencyInjection\ServiceBusExtension()]);

        $containerParameters->add($configData);
        $containerParameters->add($bootstrapContainerConfiguration->getCustomerParameters());

        $containerBuilder = new SymfonyDependencyInjection\ContainerBuilder($containerParameters);

        foreach($containerExtensions as $extension)
        {
            /** @var SymfonyDependencyInjection\Extension\Extension $extension */
            $extension->load($configData, $containerBuilder);
        }

        foreach($containerCompilerPass as $compilerPass)
        {
            $containerBuilder->addCompilerPass($compilerPass);
        }

        return $containerBuilder;
    }

    /**
     * Getting the relation of the class with the service identifier
     *
     * @param SymfonyDependencyInjection\ContainerBuilder $builder
     *
     * @return array
     */
    private function extractServicesClassRelation(SymfonyDependencyInjection\ContainerBuilder $builder): array
    {
        $relations = \array_filter(
            \array_map(
                function(string $eachService) use ($builder)
                {
                    if(true === $builder->hasDefinition($eachService))
                    {
                        return ['class' => $builder->getDefinition($eachService)->getClass(), 'id' => $eachService];
                    }

                    return null;
                },
                $builder->getServiceIds()
            )
        );

        $result = [];

        foreach($relations as $relation)
        {
            $result[$relation['class']] = $relation['id'];
        }

        return $result;
    }

    /**
     * Dumps the service container to PHP code in the cache
     *
     * @param Config\ConfigCache                          $cache
     * @param SymfonyDependencyInjection\ContainerBuilder $container
     * @param string                                      $class
     *
     * @return void
     */
    private function dumpContainer(
        Config\ConfigCache $cache,
        SymfonyDependencyInjection\ContainerBuilder $container,
        string $class
    ): void
    {
        $dumper = new SymfonyDependencyInjection\Dumper\PhpDumper($container);
        $content = (string) $dumper->dump([
                'class'      => $class,
                'base_class' => 'Container',
                'file'       => $cache->getPath()
            ]
        );

        $cache->write($content, $container->getResources());
    }

    /**
     * @param string $rootDirectoryPath
     * @param string $cacheDirectoryPath
     * @param string $environmentFilePath
     *
     * @throws BootstrapExceptions\IncorrectRootDirectoryPathException
     * @throws BootstrapExceptions\IncorrectDotEnvFilePathException
     * @throws BootstrapExceptions\IncorrectCacheDirectoryFilePathException
     */
    final protected function __construct(
        string $rootDirectoryPath,
        string $cacheDirectoryPath,
        string $environmentFilePath
    )
    {
        $this->environmentFilePath = $environmentFilePath;
        $this->cacheDirectoryPath = $this->prepareCacheDirectoryPath($cacheDirectoryPath);
        $this->rootDirectoryPath = $this->prepareRootDirectoryPath($rootDirectoryPath);

        if(false === \file_exists($this->environmentFilePath) || false === \is_readable($this->environmentFilePath))
        {
            throw new BootstrapExceptions\IncorrectDotEnvFilePathException($environmentFilePath);
        }

        $this->configureAnnotationsLoader();
    }

    /**
     * Prepare root directory path
     *
     * @param string $rootDirectoryPath
     *
     * @return string
     *
     * @throws BootstrapExceptions\IncorrectRootDirectoryPathException
     */
    private function prepareRootDirectoryPath(string $rootDirectoryPath): string
    {
        $rootDirectoryPath = \rtrim($rootDirectoryPath, '/');

        if('' === $rootDirectoryPath || false === \is_dir($rootDirectoryPath) || false === \is_readable($rootDirectoryPath))
        {
            throw new BootstrapExceptions\IncorrectRootDirectoryPathException($rootDirectoryPath);
        }

        return $rootDirectoryPath;
    }

    /**
     * Prepare cache directory
     *
     * @param string $cacheDirectoryPath
     *
     * @return string
     *
     * @throws BootstrapExceptions\IncorrectCacheDirectoryFilePathException
     */
    private function prepareCacheDirectoryPath(string $cacheDirectoryPath): string
    {
        $cacheDirectoryPath = \rtrim($cacheDirectoryPath, '/');

        try
        {
            if('' === $cacheDirectoryPath)
            {
                throw new \InvalidArgumentException($cacheDirectoryPath);
            }

            $filesystem = new Filesystem();

            if(false === $filesystem->exists($cacheDirectoryPath))
            {
                $filesystem->mkdir($cacheDirectoryPath);
            }

            $filesystem->chmod($cacheDirectoryPath, 0775, \umask());
        }
        catch(\Throwable $throwable)
        {
            throw new BootstrapExceptions\IncorrectCacheDirectoryFilePathException($cacheDirectoryPath, $throwable);
        }

        return $cacheDirectoryPath;
    }

    /**
     * Configure doctrine2 annotations loader
     *
     * @return void
     */
    private function configureAnnotationsLoader(): void
    {
        /** Configure doctrine annotations autoloader */
        foreach(\spl_autoload_functions() as $autoLoader)
        {
            if(isset($autoLoader[0]) && \is_object($autoLoader[0]))
            {
                /** @var \Composer\Autoload\ClassLoader $classLoader */
                $classLoader = $autoLoader[0];

                /** @noinspection PhpDeprecationInspection */
                AnnotationRegistry::registerLoader(
                    function(string $className) use ($classLoader)
                    {
                        return $classLoader->loadClass($className);
                    }
                );

                break;
            }
        }
    }
}
