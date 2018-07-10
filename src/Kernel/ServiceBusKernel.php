<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Kernel;

use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\MessageBus\MessageBus;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use function Desperado\ServiceBus\MessageBus\messageDispatcher;
use Desperado\ServiceBus\MessageBus\Task\Arguments\ContainerArgumentResolver;
use Desperado\ServiceBus\MessageBus\Task\Arguments\ContextArgumentResolver;
use Desperado\ServiceBus\MessageBus\Task\Arguments\MessageArgumentResolver;
use Desperado\ServiceBus\Router\OutboundMessageRouter;
use Desperado\ServiceBus\Transport\Destination;
use Desperado\ServiceBus\Transport\IncomingEnvelope;
use Desperado\ServiceBus\Transport\OutboundEnvelope;
use Desperado\ServiceBus\Transport\Publisher;
use Desperado\ServiceBus\Transport\Queue;
use Desperado\ServiceBus\Transport\QueueBind;
use Desperado\ServiceBus\Transport\Topic;
use Desperado\ServiceBus\Transport\Transport;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 *
 */
final class ServiceBusKernel
{

    /**
     * @var ContainerInterface
     */
    private $kernelLocator;

    /**
     * @var ContainerInterface
     */
    private $servicesLocator;

    /**
     * @var array<mixed, \Desperado\ServiceBus\Transport\Destination>
     */
    private $defaultDestinations = [];

    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param ContainerInterface $kernelLocator
     * @param ContainerInterface $servicesLocator
     * @param array              $services
     * @param LoggerInterface    $logger
     *
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NoMessageArgumentFound
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NonUniqueCommandHandler
     */
    public function __construct(
        ContainerInterface $kernelLocator,
        ContainerInterface $servicesLocator,
        array $services,
        LoggerInterface $logger
    )
    {
        $this->kernelLocator   = $kernelLocator;
        $this->servicesLocator = $servicesLocator;

        $this->logger = $logger;

        $this->messageBus = $this->compileMessageBus($services);
    }

    /**
     * Add default message destinations
     *
     * @param Destination ...$destinations
     *
     * @return $this
     */
    public function configureDefaultDestinations(Destination ...$destinations): self
    {
        $this->defaultDestinations = \array_merge($this->defaultDestinations, $destinations);

        return $this;
    }

    /**
     * Configure topics
     *
     * @param Topic ...$topics
     *
     * @return $this
     */
    public function addTopics(Topic ... $topics): self
    {
        foreach($topics as $topic)
        {
            $this->kernelLocator->get(Transport::class)->createTopic($topic);
        }

        return $this;
    }

    /**
     * Configure queue
     *
     * @param Queue          $queue
     * @param QueueBind|null $bindTo
     *
     * @return $this
     */
    public function addQueue(Queue $queue, QueueBind $bindTo = null): self
    {
        $this->kernelLocator->get(Transport::class)->createQueue($queue, $bindTo);

        return $this;
    }

    /**
     * Configure default messages destinations
     *
     * @param Destination ...$destinations
     *
     * @return $this
     */
    public function addDefaultDestinations(Destination ...$destinations): self
    {
        $this->kernelLocator->get(OutboundMessageRouter::class)->addDefaultRoutes($destinations);

        return $this;
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
            messageDispatcher($this->messageBus),
            self::createMessageSender(
                $this->kernelLocator->get(Transport::class)->createPublisher(),
                $this->kernelLocator->get(OutboundMessageRouter::class),
                $this->logger
            ),
            $this->logger
        );

        $this->kernelLocator
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
     * Compile message bus
     *
     * @param array $services
     *
     * @return MessageBus
     *
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NoMessageArgumentFound
     * @throws \Desperado\ServiceBus\MessageBus\Exceptions\NonUniqueCommandHandler
     */
    private function compileMessageBus(array $services): MessageBus
    {
        /** @var MessageBusBuilder $messageBusBuilder */
        $messageBusBuilder = $this->kernelLocator->get(MessageBusBuilder::class);

        foreach($services as $serviceId)
        {
            $serviceId = \sprintf('%s_service', $serviceId);

            $messageBusBuilder->configureService(
                $this->servicesLocator->get($serviceId),
                new ContainerArgumentResolver($this->servicesLocator),
                new MessageArgumentResolver(),
                new ContextArgumentResolver()
            );
        }

        return $messageBusBuilder->compile();
    }

    /**
     * Create handle message handler
     *
     * @param \Generator      $messageDispatcher
     * @param \Generator      $messageSender
     * @param LoggerInterface $logger
     *
     * @return \Generator
     */
    private static function createMessageProcessor(
        \Generator $messageDispatcher,
        \Generator $messageSender,
        LoggerInterface $logger
    ): \Generator
    {
        while(true)
        {
            /** @var IncomingEnvelope $envelope */
            $envelope = yield;

            $logger->debug(
                'Dispatching the message "{messageClass}" for processing', [
                    'messageClass'             => \get_class($envelope->denormalized()),
                    'operationId'              => $envelope->operationId(),
                    'rawMessagePayload'        => $envelope->requestBody(),
                    'normalizedMessagePayload' => $envelope->normalized(),
                    'headers'                  => $envelope->headers()
                ]
            );

            try
            {
                $messageDispatcher->send(
                    new ApplicationContext($envelope, $messageSender, $logger)
                );
            }
            catch(\Throwable $throwable)
            {
                $logger->error(
                    'Operation failed: "{errorMessage}"', [
                        'operationId'              => $envelope->operationId(),
                        'rawMessagePayload'        => $envelope->requestBody(),
                        'normalizedMessagePayload' => $envelope->normalized(),
                        'headers'                  => $envelope->headers()
                    ]
                );
            }

            unset($envelope);
        }
    }

    /**
     * Create message sender
     *
     * @param Publisher             $publisher
     * @param OutboundMessageRouter $messageRouter
     * @param LoggerInterface       $logger
     *
     * @return \Generator
     */
    private static function createMessageSender(
        Publisher $publisher,
        OutboundMessageRouter $messageRouter,
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
            $destinations = $messageRouter->destinationsFor($messageClass);

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

                self::logOutboundMessage($logger, $messageClass, $destination, $outboundEnvelope);

                $publisher->send($destination, $outboundEnvelope);
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
     * @param LoggerInterface  $logger
     * @param string           $messageClass
     * @param Destination      $destination
     * @param OutboundEnvelope $outboundEnvelope
     *
     * @return void
     */
    private static function logOutboundMessage(
        LoggerInterface $logger,
        string $messageClass,
        Destination $destination,
        OutboundEnvelope $outboundEnvelope
    ): void
    {
        $logger->debug(
            'Sending a "{messageClass}" message to "{destinationTopic}/{destinationRoutingKey}" with ' .
            'the body "{rawMessagePayload}" and the headers "{headers}"',
            [
                'messageClass'          => $messageClass,
                'destinationTopic'      => $destination->topicName(),
                'destinationRoutingKey' => $destination->routingKey(),
                'rawMessagePayload'     => $outboundEnvelope->messageContent(),
                'headers'               => $outboundEnvelope->headers()
            ]
        );
    }
}
