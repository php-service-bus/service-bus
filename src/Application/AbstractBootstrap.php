<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application;

use Desperado\Domain\ParameterBag;
use Desperado\ServiceBus\Application\Exceptions as ApplicationExceptions;
use Desperado\ServiceBus\DependencyInjection\Compiler\EntryPointCompilerPass;
use Desperado\ServiceBus\DependencyInjection\Compiler\LoggerChannelsCompilerPass;
use Desperado\ServiceBus\DependencyInjection\Compiler\ModulesCompilerPass;
use Desperado\ServiceBus\DependencyInjection\Compiler\SagaStorageCompilerPass;
use Desperado\ServiceBus\DependencyInjection\ServiceBusExtension;
use Desperado\ServiceBus\EntryPoint\EntryPoint;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Config;
use Symfony\Component\DependencyInjection;
use Symfony\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony\Component\Filesystem\Exception\IOException;
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
     * @var DependencyInjection\Container
     */
    private $container;

    /**
     * Initializing components
     *
     * @param string $rootDirectoryPath   Absolute path to the root of the application
     * @param string $environmentFilePath Absolute path to the ".env" configuration file
     * @param string $cacheDirectoryPath  Absolute path to the cache directory
     *
     * @return EntryPoint
     *
     * @throws ApplicationExceptions\IncorrectRootDirectoryPathException
     * @throws ApplicationExceptions\IncorrectDotEnvFilePathException
     * @throws ApplicationExceptions\IncorrectCacheDirectoryFilePathException
     * @throws ApplicationExceptions\ServiceBusConfigurationException
     *
     * @throws \Exception                        if an exception has been thrown when the service has been resolved
     */
    public static function boot(
        string $rootDirectoryPath,
        string $cacheDirectoryPath,
        string $environmentFilePath
    ): EntryPoint
    {
        $self = new static($rootDirectoryPath, $cacheDirectoryPath, $environmentFilePath);

        $self->loadConfiguration();
        $self->initializeContainer();

        $self->init();

        /** @var EntryPoint $entryPoint */
        $entryPoint = $self->getContainer()->get('service_bus.entry_point');

        return $entryPoint;
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
     * Get dependency injection container
     *
     * @return DependencyInjection\Container
     */
    final protected function getContainer(): DependencyInjection\Container
    {
        return $this->container;
    }

    /**
     * Load configuration
     *
     * @return void
     *
     * @throws ApplicationExceptions\ServiceBusConfigurationException
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

            throw new ApplicationExceptions\ServiceBusConfigurationException($violation->getMessage());
        }
    }

    /**
     * Initialize dependency injection container
     *
     * @return void
     */
    private function initializeContainer(): void
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
            $container->compile();

            $this->dumpContainer($configCache, $container, $containerClassName);
        }

        /** @noinspection PhpIncludeInspection */
        include $containerClassPath;

        $this->container = new $containerClassName();
    }

    /**
     * Build dependency injection container
     *
     * @return DependencyInjection\ContainerBuilder
     */
    private function buildContainer(): DependencyInjection\ContainerBuilder
    {
        $bootstrapContainerConfiguration = $this->getBootstrapContainerConfiguration();
        $bootstrapServicesDefinitions = $this->getBootstrapServicesDefinitions();

        $configData = $this->configuration->toArray();

        $applicationCompilerPass = [
            'logger_channel'  => new LoggerChannelsCompilerPass(),
            'modules'         => new ModulesCompilerPass(),
            'saga_storage'    => new SagaStorageCompilerPass($bootstrapServicesDefinitions->getSagaStorageKey()),
            'entry_point'     => new EntryPointCompilerPass(
                $bootstrapServicesDefinitions->getMessageTransportKey(),
                $bootstrapServicesDefinitions->getKernelKey(),
                $bootstrapServicesDefinitions->getApplicationContextKey()
            ),
            'event_listeners' => new RegisterListenersPass(
                'service_bus.event_dispatcher',
                'service_bus.event_listener',
                'service_bus.event_subscriber'
            )
        ];

        $containerParameters = new DependencyInjection\ParameterBag\ParameterBag([
            'service_bus.root_dir'  => $this->rootDirectoryPath,
            'service_bus.cache_dir' => $this->cacheDirectoryPath
        ]);
        $containerExtensions = new ParameterBag();
        $containerCompilerPass = new ParameterBag();

        $containerCompilerPass->add($bootstrapContainerConfiguration->getCompilerPassCollection());
        $containerCompilerPass->add($applicationCompilerPass);

        $containerExtensions->add($bootstrapContainerConfiguration->getExtensionsCollection());
        $containerExtensions->add(['service_bus' => new ServiceBusExtension()]);

        $containerParameters->add($configData);
        $containerParameters->add($bootstrapContainerConfiguration->getCustomerParameters());

        $containerBuilder = new DependencyInjection\ContainerBuilder($containerParameters);

        foreach($containerExtensions as $extension)
        {
            /** @var DependencyInjection\Extension\Extension $extension */
            $extension->load($configData, $containerBuilder);
        }

        foreach($containerCompilerPass as $compilerPass)
        {
            $containerBuilder->addCompilerPass($compilerPass);
        }

        return $containerBuilder;
    }

    /**
     * Dumps the service container to PHP code in the cache
     *
     * @param Config\ConfigCache                   $cache
     * @param DependencyInjection\ContainerBuilder $container
     * @param string                               $class
     *
     * @return void
     */
    private function dumpContainer(Config\ConfigCache $cache, DependencyInjection\ContainerBuilder $container, string $class)
    {
        $dumper = new DependencyInjection\Dumper\PhpDumper($container);
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
     * @throws ApplicationExceptions\IncorrectRootDirectoryPathException
     * @throws ApplicationExceptions\IncorrectDotEnvFilePathException
     * @throws ApplicationExceptions\IncorrectCacheDirectoryFilePathException
     * @throws IOException
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
            throw new ApplicationExceptions\IncorrectDotEnvFilePathException($environmentFilePath);
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
     * @throws ApplicationExceptions\IncorrectRootDirectoryPathException
     */
    private function prepareRootDirectoryPath(string $rootDirectoryPath): string
    {
        $rootDirectoryPath = \rtrim($rootDirectoryPath, '/');

        if('' === $rootDirectoryPath || false === \is_dir($rootDirectoryPath) || false === \is_readable($rootDirectoryPath))
        {
            throw new ApplicationExceptions\IncorrectRootDirectoryPathException($this->rootDirectoryPath);
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
     * @throws IOException
     */
    private function prepareCacheDirectoryPath(string $cacheDirectoryPath): string
    {
        $cacheDirectoryPath = \rtrim($cacheDirectoryPath, '/');

        if('' === $cacheDirectoryPath)
        {
            throw new ApplicationExceptions\IncorrectCacheDirectoryFilePathException($cacheDirectoryPath);
        }

        $filesystem = new Filesystem();

        if(false === $filesystem->exists($cacheDirectoryPath))
        {
            $filesystem->mkdir($cacheDirectoryPath);
        }

        $filesystem->chmod($cacheDirectoryPath, 0775, \umask());

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
