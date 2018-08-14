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
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\MessageBus\Exceptions\NoMessageHandlersFound;
use Desperado\ServiceBus\MessageBus\MessageBus;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ContainerArgumentResolver;
use Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\ContextArgumentResolver;
use Desperado\ServiceBus\MessageBus\MessageHandler\Resolvers\MessageArgumentResolver;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\OutboundMessage\OutboundMessageRoutes;
use Desperado\ServiceBus\Transport\IncomingEnvelope;
use Desperado\ServiceBus\Transport\OutboundEnvelope;
use Desperado\ServiceBus\Transport\Publisher;
use Desperado\ServiceBus\Transport\Queue;
use Desperado\ServiceBus\Transport\Transport;
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

        $this->messageBus = $this->buildMessageBus($container);
    }

    /**
     * Receive transport configurator
     *
     * @return TransportConfigurator
     */
    public function transportConfigurator(): TransportConfigurator
    {
        return $this->kernelContainer->get(TransportConfigurator::class);
    }

    /**
     * By default, messages are logged with a level of "debug"
     * Logging can be forcibly disabled for all levels
     *
     * @return void
     */
    public function disableMessagesPayloadLogging(): void
    {
        static::$payloadLoggingForciblyDisabled = true;
    }

    /**
     * Start message listen
     *
     * @param Queue $queue
     *
     * @return void
     */
    public function listen(Queue $queue): void
    {
        $messageProcessor = self::createMessageProcessor(
            $this->messageBus,
            self::createMessageSender(
                $this->kernelContainer->get(Transport::class)->createPublisher(),
                $this->kernelContainer->get(OutboundMessageRoutes::class),
                $this->kernelContainer->get(LoggerInterface::class)
            ),
            $this->kernelContainer->get(LoggerInterface::class)
        );

        $this->kernelContainer
            ->get(Transport::class)
            ->createConsumer($queue)
            ->listen(
                static function(IncomingEnvelope $envelope) use ($messageProcessor): void
                {
                    $messageProcessor->send($envelope);
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
     * @param array<mixed, string>              $serviceIds
     * @param MessageBusBuilder  $messagesBusBuilder
     * @param ContainerInterface $servicesLocator
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
     * Create handle message handler
     *
     * @param MessageBus      $messageBus
     * @param \Generator      $messageSender
     * @param LoggerInterface $logger
     *
     * @return \Generator
     */
    private static function createMessageProcessor(
        MessageBus $messageBus,
        \Generator $messageSender,
        LoggerInterface $logger
    ): \Generator
    {
        while(true)
        {
            /** @var IncomingEnvelope $envelope */
            $envelope = yield;

            Loop::run(
                static function() use ($messageBus, $envelope, $messageSender, $logger): \Generator
                {
                    try
                    {
                        self::beforeDispatch($envelope, $logger);

                        yield $messageBus->dispatch(
                            new KernelContext($envelope, $messageSender, $logger)
                        );
                    }
                    catch(NoMessageHandlersFound $exception)
                    {
                        $logger->debug($exception->getMessage(), ['operationId' => $envelope->operationId()]);
                    }
                    catch(\Throwable $throwable)
                    {
                        $logger->critical($throwable->getMessage(), [
                            'operationId' => $envelope->operationId(),
                            'file'        => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                        ]);
                        /** @todo: retry message? */
                    }
                }
            );

            unset($envelope);
        }
    }

    /**
     * Create message sender
     *
     * @param Publisher             $publisher
     * @param OutboundMessageRoutes $messageRoutes
     * @param LoggerInterface       $logger
     *
     * @return \Generator
     */
    private static function createMessageSender(
        Publisher $publisher,
        OutboundMessageRoutes $messageRoutes,
        LoggerInterface $logger
    ): \Generator
    {
        while(true)
        {
            /**
             * @var Message          $message
             * @var IncomingEnvelope $incomingEnvelope
             */
            [$message, $incomingEnvelope] = yield;

            $messageClass = \get_class($message);
            $destinations = $messageRoutes->destinationsFor($messageClass);

            foreach($destinations as $destination)
            {
                /** @var Destination $destination */

                $outboundEnvelope = self::createOutboundEnvelope(
                    $publisher,
                    $incomingEnvelope->operationId(),
                    $message, [
                        'x-message-class'         => $messageClass,
                        'x-created-after-message' => \get_class($incomingEnvelope->denormalized()),
                        'x-hostname'              => \gethostname()
                    ]
                );

                self::beforeMessageSend($logger, $messageClass, $destination, $outboundEnvelope);

                try
                {
                    $publisher->send($destination, $outboundEnvelope);
                }
                catch(\Throwable $throwable)
                {
                    self::onSendMessageFailed($outboundEnvelope, $messageClass, $throwable, $logger);
                }
            }

            unset($message, $incomingEnvelope, $messageClass, $destinations);
        }
    }

    /**
     * Create outbound message package
     *
     * @param Publisher $publisher
     * @param string    $operationId
     * @param Message   $message
     * @param array     $headers
     *
     * @return OutboundEnvelope
     */
    private static function createOutboundEnvelope(
        Publisher $publisher,
        string $operationId,
        Message $message,
        array $headers
    ): OutboundEnvelope
    {
        $envelope = $publisher->createEnvelope($message, $headers);

        $envelope->setupMessageId($operationId);
        $envelope->makeMandatory();
        $envelope->makePersistent();

        return $envelope;
    }

    /**
     * @param IncomingEnvelope $envelope
     * @param LoggerInterface  $logger
     *
     * @return void
     */
    private static function beforeDispatch(IncomingEnvelope $envelope, LoggerInterface $logger): void
    {
        $logger->debug('Dispatching the message "{messageClass}"', [
                'messageClass' => \get_class($envelope->denormalized()),
                'operationId'  => $envelope->operationId(),
            ]
        );

        if(false === static::$payloadLoggingForciblyDisabled)
        {
            $logger->debug('Incoming message payload: "{rawMessagePayload}"', [
                    'rawMessagePayload'        => $envelope->requestBody(),
                    'normalizedMessagePayload' => $envelope->normalized(),
                    'headers'                  => $envelope->headers(),
                    'operationId'              => $envelope->operationId()
                ]
            );
        }
    }

    /**
     * @param OutboundEnvelope $outboundEnvelope
     * @param string           $messageClass
     * @param \Throwable       $throwable
     * @param LoggerInterface  $logger
     *
     * @return void
     */
    private static function onSendMessageFailed(
        OutboundEnvelope $outboundEnvelope,
        string $messageClass,
        \Throwable $throwable,
        LoggerInterface $logger
    ): void
    {
        $logger->critical(
            'Error sending message "{messageClass}" to broker: "{exceptionMessage}"', [
                'messageClass'     => $messageClass,
                'exceptionMessage' => $throwable->getMessage()
            ]
        );

        if(false === static::$payloadLoggingForciblyDisabled)
        {
            $logger->debug('The body of the unsent message: {rawMessagePayload}', [
                    'rawMessagePayload' => $outboundEnvelope->messageContent(),
                    'headers'           => $outboundEnvelope->headers()
                ]
            );
        }
    }

    /**
     * @param LoggerInterface  $logger
     * @param string           $messageClass
     * @param Destination      $destination
     * @param OutboundEnvelope $outboundEnvelope
     *
     * @return void
     */
    private static function beforeMessageSend(
        LoggerInterface $logger,
        string $messageClass,
        Destination $destination,
        OutboundEnvelope $outboundEnvelope
    ): void
    {
        $logger->debug(
            'Sending a "{messageClass}" message to "{destinationTopic}/{destinationRoutingKey}"', [
                'messageClass'          => $messageClass,
                'destinationTopic'      => $destination->topicName(),
                'destinationRoutingKey' => $destination->routingKey()
            ]
        );

        if(false === static::$payloadLoggingForciblyDisabled)
        {
            $logger->debug(
                'Sending message: "{rawMessagePayload}"', [
                    'rawMessagePayload' => $outboundEnvelope->messageContent(),
                    'headers'           => $outboundEnvelope->headers()
                ]
            );
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
