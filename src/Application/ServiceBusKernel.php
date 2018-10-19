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
     * Use default handler for signal "SIGINT"
     *
     * @param int $stopDelay
     *
     * @return $this
     *
     * @throws Loop\UnsupportedFeatureException
     */
    public function useDefaultStopSignalHandler(int $stopDelay = 10000): self
    {
        /** @var LoggerInterface $logger */
        $logger = $this->getKernelContainerService(LoggerInterface::class);

        Loop::onSignal(
            \SIGINT,
            function() use ($stopDelay, $logger): void
            {
                $logger->info('A signal SIGINT(2) was received');

                $this->entryPoint->stop($stopDelay);
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
