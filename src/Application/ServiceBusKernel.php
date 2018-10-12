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

use function Amp\call;
use Amp\Loop;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\LoopMonitor\LoopBlockDetector;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Queue;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;
use Desperado\ServiceBus\MessageBus\MessageBus;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ContainerArgumentResolver;
use Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ContextArgumentResolver;
use Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\MessageArgumentResolver;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service bus application kernel
 */
final class ServiceBusKernel
{
    private const KERNEL_LOCATOR_INDEX = 'service_bus.kernel_locator';
    private const SERVICES_LOCATOR     = 'service_bus.services_locator';

    /**
     * Custom service locator for application kernel only
     *
     * @var ContainerInterface
     */
    private $kernelContainer;

    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var Queue|null
     */
    private $listenQueue;

    /**
     * Logging is forced off for all levels
     *
     * @var bool
     */
    private static $payloadLoggingForciblyDisabled = false;

    /**
     * @param ContainerInterface $container
     *
     * @throws \Throwable
     */
    public function __construct(ContainerInterface $container)
    {
        $this->kernelContainer = $container->get(self::KERNEL_LOCATOR_INDEX);
        $this->transport       = $this->kernelContainer->get(Transport::class);

        $this->messageBus = $this->buildMessageBus($container);
    }

    /**
     * Enable watch for event loop blocking
     * DO NOT USE IN PRODUCTION environment
     *
     * @return self
     */
    public function monitorLoopBlock(): self
    {
        $this->kernelContainer->get(LoopBlockDetector::class)->listen();

        return $this;
    }

    /**
     * By default, messages are logged with a level of "debug"
     * Logging payload can be forcibly disabled for all levels
     *
     * @return void
     */
    public function disableMessagesPayloadLogging(): void
    {
        static::$payloadLoggingForciblyDisabled = true;
    }

    /**
     * Use default handler for signal "SIGINT"
     *
     * @param int $stopDelay
     *
     * @return self
     *
     * @throws Loop\UnsupportedFeatureException
     */
    public function useDefaultStopSignalHandler(int $stopDelay = 10000): self
    {
        $logger = $this->kernelContainer->get(LoggerInterface::class);

        Loop::onSignal(
            \SIGINT,
            function() use ($stopDelay, $logger): void
            {
                $logger->info('A signal SIGINT(2) was received');

                $this->stop($stopDelay);
            }
        );

        return $this;
    }

    /**
     * Start message listen
     *
     * @param Queue $queue
     *
     * @return Promise<null>
     */
    public function listen(Queue $queue): Promise
    {
        $this->listenQueue = $queue;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(Queue $queue): \Generator
            {
                /** @var \Amp\Iterator $iterator */
                $iterator = yield $this->transport->consume($queue);

                while(yield $iterator->advance())
                {
                    /** @var IncomingPackage $package */
                    $package = $iterator->getCurrent();

                    yield $package->ack();
                }
            },
            $queue
        );
    }

    /**
     * @param int $interval
     *
     * @return void
     */
    public function stop(int $interval): void
    {
        /** @var LoggerInterface $logger */
        $logger = $this->kernelContainer->get(LoggerInterface::class);

        Loop::defer(
            function() use ($interval, $logger): \Generator
            {
                yield $this->transport->stop($this->listenQueue);

                $logger->info('Handler will stop after {duration} seconds', ['duration' => $interval / 1000]);

                Loop::delay(
                    $interval,
                    static function() use ($logger): void
                    {
                        $logger->info('The event loop has been stopped');

                        Loop::stop();
                    }
                );
            }
        );
    }

    /**
     * @param ContainerInterface $globalContainer
     *
     * @return MessageBus
     *
     * @throws \Throwable
     */
    private function buildMessageBus(ContainerInterface $globalContainer): MessageBus
    {
        /** @var \Symfony\Component\DependencyInjection\Container $globalContainer */

        /** @var MessageBusBuilder $messagesBusBuilder */
        $messagesBusBuilder = $this->kernelContainer->get(MessageBusBuilder::class);

        /** @psalm-suppress UndefinedMethod */
        $this->registerServices(
            $globalContainer->getParameter('service_bus.services_map'),
            $messagesBusBuilder,
            $globalContainer->get(self::SERVICES_LOCATOR)
        );

        /** @psalm-suppress UndefinedMethod */
        $this->registerSagas(
            $globalContainer->getParameter('service_bus.sagas'),
            $messagesBusBuilder
        );

        return $messagesBusBuilder->compile();
    }

    /**
     * Register event\command handlers from services
     *
     * @param array<mixed, string> $serviceIds
     * @param MessageBusBuilder    $messagesBusBuilder
     * @param ContainerInterface   $servicesLocator
     *
     * @return void
     *
     * @throws \Throwable
     */
    private function registerServices(
        array $serviceIds,
        MessageBusBuilder $messagesBusBuilder,
        ContainerInterface $servicesLocator
    ): void
    {
        $resolvers   = self::createDefaultResolvers();
        $resolvers[] = new ContainerArgumentResolver($servicesLocator);

        foreach($serviceIds as $serviceId)
        {
            $messagesBusBuilder->addService(
                $servicesLocator->get(\sprintf('%s_service', $serviceId)),
                ...$resolvers
            );
        }
    }

    /**
     * Register sagas listeners
     *
     * @param array             $sagas
     * @param MessageBusBuilder $messageBusBuilder
     *
     * @return void
     *
     * @throws \Throwable
     */
    private function registerSagas(array $sagas, MessageBusBuilder $messageBusBuilder): void
    {
        $resolvers = self::createDefaultResolvers();

        foreach($sagas as $sagaClass)
        {
            $messageBusBuilder->addSaga($sagaClass, ...$resolvers);
        }
    }

    /**
     * Create default argument resolvers
     *
     * @return array<mixed, \Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ArgumentResolver>
     */
    private static function createDefaultResolvers(): array
    {
        return [
            new ContextArgumentResolver(),
            new MessageArgumentResolver()
        ];
    }
}
