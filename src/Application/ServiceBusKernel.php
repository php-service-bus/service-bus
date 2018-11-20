<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application;

use Amp\Loop;
use Desperado\ServiceBus\Endpoint\Endpoint;
use Desperado\ServiceBus\Endpoint\EndpointRouter;
use Desperado\ServiceBus\EntryPoint\EntryPoint;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;
use Desperado\ServiceBus\Infrastructure\Watchers\FileChangesWatcher;
use Desperado\ServiceBus\Infrastructure\Watchers\GarbageCollectorWatcher;
use Desperado\ServiceBus\Infrastructure\Watchers\LoopBlockWatcher;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service bus application kernel
 */
final class ServiceBusKernel
{
    /**
     * DIC
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Application entry point
     *
     * @var EntryPoint
     */
    private $entryPoint;

    /**
     * Messages transport interface
     *
     * @var Transport
     */
    private $transport;

    /**
     * @param ContainerInterface $globalContainer
     *
     * @throws \Throwable
     */
    public function __construct(ContainerInterface $globalContainer)
    {
        $this->container = $globalContainer;

        /** @var \Symfony\Component\DependencyInjection\ServiceLocator $serviceLocator */
        $serviceLocator = $this->container->get('service_bus.public_services_locator');

        /** @var Transport $transport */
        $transport = $serviceLocator->get(Transport::class);
        /** @var EntryPoint $entryPoint */
        $entryPoint = $serviceLocator->get(EntryPoint::class);

        $this->transport  = $transport;
        $this->entryPoint = $entryPoint;
    }

    /**
     * Enable watch for event loop blocking
     * DO NOT USE IN PRODUCTION environment
     *
     * @return $this
     */
    public function monitorLoopBlock(): self
    {
        /** @var LoopBlockWatcher $loopBlockWatcher */
        $loopBlockWatcher = $this->getKernelContainerService(LoopBlockWatcher::class);

        $loopBlockWatcher->run();

        return $this;
    }

    /**
     * Enable periodic forced launch of the garbage collector
     *
     * @return $this
     */
    public function enableGarbageCleaning(): self
    {
        /** @var GarbageCollectorWatcher $garbageCollectorWatcher */
        $garbageCollectorWatcher = $this->getKernelContainerService(GarbageCollectorWatcher::class);

        $garbageCollectorWatcher->run();

        return $this;
    }

    /**
     * Use default handler for signal "SIGINT" and "SIGTERM"
     *
     * @param int               $stopDelay The delay before the completion (in seconds)
     * @param array<mixed, int> $signals   Processed signals
     *
     * @return $this
     *
     * @throws Loop\UnsupportedFeatureException This might happen if ext-pcntl is missing and the loop driver doesn't
     *                                          support another way to dispatch signals
     */
    public function useDefaultStopSignalHandler(int $stopDelay = 10, array $signals = [\SIGINT, \SIGTERM]): self
    {
        $stopDelay = 0 >= $stopDelay ? 1 : $stopDelay;

        /** @var LoggerInterface $logger */
        $logger = $this->getKernelContainerService('service_bus.logger');

        $handler = function(string $watcherId, int $signalId) use ($stopDelay, $logger): void
        {
            $logger->info(
                'A signal "{signalId}" was received', [
                    'signalId'  => $signalId,
                    'watcherId' => $watcherId
                ]
            );

            $this->entryPoint->stop($stopDelay);
        };

        foreach($signals as $signal)
        {
            Loop::onSignal($signal, $handler);
        }

        return $this;
    }

    /**
     * Shut down after N seconds
     *
     * @param int $seconds
     *
     * @return self
     */
    public function stopAfter(int $seconds): self
    {
        $seconds = 0 >= $seconds ? 1 : $seconds;

        Loop::delay(
            $seconds * 1000,
            function() use ($seconds): void
            {
                /** @var LoggerInterface $logger */
                $logger = $this->getKernelContainerService('service_bus.logger');

                $logger->info('The demon\'s lifetime has expired ({lifetime} seconds)', ['lifetime' => $seconds]);

                $this->entryPoint->stop();
            }
        );

        return $this;
    }

    /**
     * Enable file change monitoring. If the application files have been modified, quit
     *
     * @param string $directoryPath
     * @param int    $checkInterval Hash check interval (in seconds)
     * @param int    $stopDelay     The delay before the completion (in seconds)
     *
     * @return self
     */
    public function stopWhenFilesChange(string $directoryPath, int $checkInterval = 30, int $stopDelay = 5): self
    {
        $checkInterval = 0 >= $checkInterval ? 1 : $checkInterval;
        $watcher       = new FileChangesWatcher($directoryPath);

        Loop::repeat(
            $checkInterval * 1000,
            function() use ($watcher, $stopDelay): \Generator
            {
                /** @var bool $changed */
                $changed = yield $watcher->compare();

                if(true === $changed)
                {
                    /** @var LoggerInterface $logger */
                    $logger = $this->getKernelContainerService('service_bus.logger');

                    $logger->info(
                        'Application files have been changed. Shut down after {delay} seconds',
                        ['delay' => $stopDelay]
                    );

                    $this->entryPoint->stop($stopDelay);
                }
            }
        );

        return $this;
    }

    /**
     * Apply specific route to deliver a message
     * By default, messages will be sent to the application transport. If a different option is specified for the
     * message, it will be sent only to it
     *
     * @param string   $messageClass
     * @param Endpoint $endpoint
     *
     * @return void
     */
    public function registerMessageCustomEndpoint(string $messageClass, Endpoint $endpoint): void
    {
        /** @var EndpointRouter $entryPointRouter */
        $entryPointRouter = $this->getKernelContainerService(EndpointRouter::class);

        $entryPointRouter->registerRoute($messageClass, $endpoint);
    }

    /**
     * @return Transport
     */
    public function transport(): Transport
    {
        return $this->transport;
    }

    /**
     * @return EntryPoint
     *
     * @throws \Throwable
     */
    public function entryPoint(): EntryPoint
    {
        return $this->entryPoint;
    }

    /**
     * @param string $service
     *
     * @return object
     */
    private function getKernelContainerService(string $service): object
    {
        /** @var \Symfony\Component\DependencyInjection\ServiceLocator $serviceLocator */
        $serviceLocator = $this->container->get('service_bus.public_services_locator');

        /** @var object $object */
        $object = $serviceLocator->get($service);

        return $object;
    }
}
