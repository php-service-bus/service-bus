<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Application;

use Amp\Loop;
use Amp\Promise;
use Psr\Log\LoggerInterface;
use ServiceBus\Endpoint\Endpoint;
use ServiceBus\Endpoint\EndpointRouter;
use ServiceBus\EntryPoint\EntryPoint;
use ServiceBus\Infrastructure\Watchers\GarbageCollectorWatcher;
use ServiceBus\Infrastructure\Watchers\LoopBlockWatcher;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Queue;
use ServiceBus\Transport\Common\QueueBind;
use ServiceBus\Transport\Common\Topic;
use ServiceBus\Transport\Common\TopicBind;
use ServiceBus\Transport\Common\Transport;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function Amp\delay;

/**
 * Service bus application kernel.
 */
final class ServiceBusKernel
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntryPoint
     */
    private $entryPoint;

    /**
     * @var Transport
     */
    private $transport;

    public function __construct(ContainerInterface $globalContainer)
    {
        $this->container = $globalContainer;

        /** @var \Symfony\Component\DependencyInjection\ServiceLocator $serviceLocator */
        $serviceLocator = $this->container->get('service_bus.public_services_locator');

        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @var EntryPoint $entryPoint
         */
        $entryPoint = $serviceLocator->get(EntryPoint::class);

        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @var Transport $transport
         */
        $transport = $serviceLocator->get(Transport::class);

        $this->entryPoint = $entryPoint;
        $this->transport  = $transport;
    }

    /**
     * Create queue and bind to topic(s)
     * If the topic to which we binds does not exist, it will be created.
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail
     * @throws \ServiceBus\Transport\Common\Exceptions\CreateQueueFailed
     * @throws \ServiceBus\Transport\Common\Exceptions\BindFailed
     */
    public function createQueue(Queue $queue, QueueBind ...$binds): Promise
    {
        return $this->transport->createQueue($queue, ...$binds);
    }

    /**
     * Create topic and bind them
     * If the topic to which we binds does not exist, it will be created.
     *
     * @throws \ServiceBus\Transport\Common\Exceptions\ConnectionFail
     * @throws \ServiceBus\Transport\Common\Exceptions\CreateTopicFailed
     * @throws \ServiceBus\Transport\Common\Exceptions\BindFailed
     */
    public function createTopic(Topic $topic, TopicBind ...$binds): Promise
    {
        return $this->transport->createTopic($topic, ...$binds);
    }

    /**
     * Run the listener on the specified queues.
     */
    public function run(Queue ...$queues): Promise
    {
        return $this->entryPoint->listen(...$queues);
    }

    /**
     * Enable watch for event loop blocking.
     * DO NOT USE IN PRODUCTION environment.
     */
    public function monitorLoopBlock(): self
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @var LoopBlockWatcher $loopBlockWatcher
         */
        $loopBlockWatcher = $this->getKernelContainerService(LoopBlockWatcher::class);

        $loopBlockWatcher->run();

        return $this;
    }

    /**
     * Enable periodic forced launch of the garbage collector.
     */
    public function enableGarbageCleaning(): self
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @var GarbageCollectorWatcher $garbageCollectorWatcher
         */
        $garbageCollectorWatcher = $this->getKernelContainerService(GarbageCollectorWatcher::class);

        $garbageCollectorWatcher->run();

        return $this;
    }

    /**
     * Use default handler for signal "SIGINT" and "SIGTERM".
     *
     * @psalm-param positive-int $stopDelay The delay before the completion (in seconds)
     * @psalm-param list<int>    $signals   Processed signals
     */
    public function useDefaultStopSignalHandler(int $stopDelay = 10, array $signals = [\SIGINT, \SIGTERM]): self
    {
        try
        {
            /**
             * @noinspection PhpUnhandledExceptionInspection
             *
             * @var LoggerInterface $logger
             */
            $logger = $this->getKernelContainerService(LoggerInterface::class);

            $handler = function (string $watcherId, int $signalId) use ($stopDelay, $logger): \Generator
            {
                yield delay($stopDelay * 1000);

                $logger->info(
                    'A signal "{signalId}" was received',
                    [
                        'signalId'  => $signalId,
                        'watcherId' => $watcherId,
                    ]
                );

                $this->entryPoint->stop();
            };

            foreach ($signals as $signal)
            {
                /** @noinspection PhpUnhandledExceptionInspection */
                Loop::onSignal($signal, $handler);
            }

            return $this;
        }
        catch (\Throwable $throwable)
        {
            throw new \LogicException(\sprintf('Incorrect signals configuration: %s', $throwable->getMessage()));
        }
    }

    /**
     * Shut down after N seconds.
     *
     * @psalm-param positive-int $seconds
     */
    public function stopAfter(int $seconds): self
    {
        Loop::delay(
            $seconds * 1000,
            function () use ($seconds): void
            {
                /** @var LoggerInterface $logger */
                $logger = $this->getKernelContainerService(LoggerInterface::class);

                $logger->info('The demon\'s lifetime has expired ({lifetime} seconds)', ['lifetime' => $seconds]);

                $this->entryPoint->stop();
            }
        );

        return $this;
    }

    /**
     * Apply specific route to deliver a messages
     * By default, messages will be sent to the application transport. If a different option is specified for the
     * message, it will be sent only to it.
     */
    public function registerEndpointForMessages(Endpoint $endpoint, string ...$messages): self
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @var EndpointRouter $entryPointRouter
         */
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
     */
    public function registerDestinationForMessages(DeliveryDestination $deliveryDestination, string ...$messages): self
    {
        /**
         * @noinspection PhpUnhandledExceptionInspection
         *
         * @var Endpoint $applicationEndpoint
         */
        $applicationEndpoint = $this->getKernelContainerService(Endpoint::class);

        $newEndpoint = $applicationEndpoint->withNewDeliveryDestination($deliveryDestination);

        return $this->registerEndpointForMessages($newEndpoint, ...$messages);
    }

    /**
     * @throws \Throwable Unknown service
     */
    private function getKernelContainerService(string $service): object
    {
        /** @var \Symfony\Component\DependencyInjection\ServiceLocator $serviceLocator */
        $serviceLocator = $this->container->get('service_bus.public_services_locator');

        /**
         * @noinspection PhpUnnecessaryLocalVariableInspection
         *
         * @var object $object
         */
        $object = $serviceLocator->get($service);

        return $object;
    }
}
