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
use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
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

        $this->transport  = $serviceLocator->get(Transport::class);
        $this->entryPoint = $serviceLocator->get(EntryPoint::class);
    }

    /**
     * Enable watch for event loop blocking
     * DO NOT USE IN PRODUCTION environment
     *
     * @return $this
     */
    public function monitorLoopBlock(): self
    {
        /** @var \Symfony\Component\DependencyInjection\ServiceLocator $serviceLocator */
        $serviceLocator = $this->container->get('service_bus.public_services_locator');

        $serviceLocator->get(LoopBlockWatcher::class)->run();

        return $this;
    }

    /**
     * Enable periodic forced launch of the garbage collector
     *
     * @return $this
     */
    public function enableGarbageCleaning(): self
    {
        /** @var \Symfony\Component\DependencyInjection\ServiceLocator $serviceLocator */
        $serviceLocator = $this->container->get('service_bus.public_services_locator');

        $serviceLocator->get(GarbageCollectorWatcher::class)->run();

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
        $logger = $this->container->get(LoggerInterface::class);

        Loop::onSignal(
            \SIGINT,
            function() use ($stopDelay, $logger): void
            {
                $logger->info('A signal SIGINT(2) was received');

                if(null !== $this->entryPoint)
                {
                    $this->entryPoint->stop($stopDelay);

                    return;
                }

                Loop::stop();
            }
        );

        return $this;
    }

    /**
     * Register command handler
     * For 1 command there can be only 1 handler
     *
     * @param Command|string $command Command object or class
     * @param callable       $handler
     *
     * @return $this
     *
     * @throws \Desperado\ServiceBus\MessageRouter\Exceptions\InvalidCommandClassSpecified
     * @throws \Desperado\ServiceBus\MessageRouter\Exceptions\MultipleCommandHandlersNotAllowed
     */
    public function registerCommandHandler($command, callable $handler): self
    {
        $this->entryPoint->registerCommandHandler($command, $handler);

        return $this;
    }

    /**
     * Add event listener
     * For each event there can be many listeners
     *
     * @param Event|string $event Event object or class
     * @param callable     $handler
     *
     * @return $this
     *
     * @throws \Desperado\ServiceBus\MessageRouter\Exceptions\InvalidEventClassSpecified
     */
    public function registerEventListener($event, callable $handler): self
    {
        $this->entryPoint->registerEventListener($event, $handler);

        return $this;
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
}
