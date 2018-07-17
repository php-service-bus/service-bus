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

use Amp\Loop;
use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\MessageBus\Exceptions\NoMessageHandlersFound;
use Desperado\ServiceBus\MessageBus\MessageBus;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
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
            $this->messageBus,
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
                            new ApplicationContext($envelope, $messageSender, $logger)
                        );
                    }
                    catch(NoMessageHandlersFound $exception)
                    {
                        $logger->debug($exception->getMessage(), ['operationId' => $envelope->operationId()]);
                    }
                    catch(\Throwable $throwable)
                    {
                        $logger->critical($throwable->getMessage(), ['operationId' => $envelope->operationId()]);
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
        $logger->info('Dispatching the message "{messageClass}"', [
                'messageClass' => \get_class($envelope->denormalized()),
                'operationId'  => $envelope->operationId(),
            ]
        );

        $logger->debug('Incoming message payload: "{rawMessagePayload}"', [
                'rawMessagePayload'        => $envelope->requestBody(),
                'normalizedMessagePayload' => $envelope->normalized(),
                'headers'                  => $envelope->headers(),
                'operationId'              => $envelope->operationId()
            ]
        );
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

        $logger->debug('The body of the unsent message: {rawMessagePayload}', [
                'rawMessagePayload' => $outboundEnvelope->messageContent(),
                'headers'           => $outboundEnvelope->headers()
            ]
        );
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
        $logger->info(
            'Sending a "{messageClass}" message to "{destinationTopic}/{destinationRoutingKey}"', [
                'messageClass'          => $messageClass,
                'destinationTopic'      => $destination->topicName(),
                'destinationRoutingKey' => $destination->routingKey()
            ]
        );

        $logger->debug(
            'Sending message: "{rawMessagePayload}"', [
                'rawMessagePayload' => $outboundEnvelope->messageContent(),
                'headers'           => $outboundEnvelope->headers()
            ]
        );
    }
}
