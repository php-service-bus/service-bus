<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Application;

use Amp\Loop;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use ServiceBus\Endpoint\Endpoint;
use ServiceBus\Endpoint\EndpointRouter;
use ServiceBus\EntryPoint\EntryPoint;
use ServiceBus\Infrastructure\Watchers\FileChangesWatcher;
use ServiceBus\Infrastructure\Watchers\GarbageCollectorWatcher;
use ServiceBus\Infrastructure\Watchers\LoopBlockWatcher;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Common\Topic;
use ServiceBus\Transport\Common\TopicBind;
use ServiceBus\Transport\Common\Transport;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service bus application kernel.
 */
final class ServiceBusKernel
{
    /**
     * DIC.
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Application entry point.
     *
     * @var EntryPoint
     */
    private $entryPoint;

    /**
     * Transport implementation.
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

        /** @var EntryPoint $entryPoint */
        $entryPoint = $serviceLocator->get(EntryPoint::class);

        /** @var Transport $transport */
        $transport = $serviceLocator->get(Transport::class);

        $this->entryPoint = $entryPoint;
        $this->transport  = $transport;
    }

    /**
     * Create queue and bind to topic(s)
     * If the topic to which we binds does not exist, it will be created.
     *
     * @param Queue     $queue
     * @param QueueBind ...$binds
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\BindFailed
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail
     * @throws \ServiceBus\Transport\Common\Exceptions\CreateQueueFailed
     *
     * @return Promise
     */
    public function createQueue(Queue $queue, QueueBind ...$binds): Promise
    {
        return $this->transport->createQueue($queue, ...$binds);
    }

    /**
     * Create topic and bind them
     * If the topic to which we binds does not exist, it will be created.
     *
     * @param Topic     $topic
     * @param TopicBind ...$binds
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\BindFailed
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail
     * @throws \ServiceBus\Transport\Common\Exceptions\CreateTopicFailed
     *
     * @return Promise
     */
    public function createTopic(Topic $topic, TopicBind ...$binds): Promise
    {
        return $this->transport->createTopic($topic, ...$binds);
    }

    /**
     * Run application.
     *
     * @param Queue ...$queues
     *
     * @return Promise
     */
    public function run(Queue ...$queues): Promise
    {
        return $this->entryPoint->listen(...$queues);
    }

    /**
     * Enable watch for event loop blocking
     * DO NOT USE IN PRODUCTION environment.
     *
     * @throws \Throwable
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
     * Enable periodic forced launch of the garbage collector.
     *
     * @throws \Throwable
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
     * Use default handler for signal "SIGINT" and "SIGTERM".
     *
     * @psalm-param array<mixed, int> $signals
     *
     * @param int   $stopDelay The delay before the completion (in seconds)
     * @param int[] $signals   Processed signals
     *
     * @throws Loop\UnsupportedFeatureException This might happen if ext-pcntl is missing and the loop driver doesn't
     *                                          support another way to dispatch signals
     * @throws \Throwable
     *
     * @return $this
     */
    public function useDefaultStopSignalHandler(int $stopDelay = 10, array $signals = [\SIGINT, \SIGTERM]): self
    {
        $stopDelay = 0 >= $stopDelay ? 1 : $stopDelay;

        /** @var LoggerInterface $logger */
        $logger = $this->getKernelContainerService('service_bus.logger');

        $handler = function(string $watcherId, int $signalId) use ($stopDelay, $logger): void
        {
            $logger->info(
                'A signal "{signalId}" was received',
                [
                    'signalId'  => $signalId,
                    'watcherId' => $watcherId,
                ]
            );

            $this->entryPoint->stop($stopDelay);
        };

        foreach ($signals as $signal)
        {
            Loop::onSignal($signal, $handler);
        }

        return $this;
    }

    /**
     * Shut down after N seconds.
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
     * Enable file change monitoring. If the application files have been modified, quit.
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

                if (true === $changed)
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
     * Apply specific route to deliver a messages
     * By default, messages will be sent to the application transport. If a different option is specified for the
     * message, it will be sent only to it.
     *
     * @param Endpoint $endpoint
     * @param string   ...$messages
     *
     * @throws \Throwable
     *
     * @return ServiceBusKernel
     */
    public function registerEndpointForMessages(Endpoint $endpoint, string ...$messages): self
    {
        /** @var EndpointRouter $entryPointRouter */
        $entryPointRouter = $this->getKernelContainerService(EndpointRouter::class);

        /** @psalm-var class-string $messageClass */
        foreach ($messages as $messageClass)
        {
            $entryPointRouter->registerRoute($messageClass, $endpoint);
        }

        return $this;
    }

    /**
     * Like the registerEndpointForMessages method, it adds a custom message delivery route.
     * The only difference is that the route is specified for the current application transport.
     *
     * @param DeliveryDestination $deliveryDestination
     * @param string              ...$messages
     *
     * @throws \Throwable
     * @throws \Throwable
     *
     * @return ServiceBusKernel
     */
    public function registerDestinationForMessages(DeliveryDestination $deliveryDestination, string ...$messages): self
    {
        /** @var Endpoint $applicationEndpoint */
        $applicationEndpoint = $this->getKernelContainerService(Endpoint::class);

        $newEndpoint = $applicationEndpoint->withNewDeliveryDestination($deliveryDestination);

        return $this->registerEndpointForMessages($newEndpoint, ...$messages);
    }

    /**
     * @param string $service
     *
     * @throws \Throwable
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
