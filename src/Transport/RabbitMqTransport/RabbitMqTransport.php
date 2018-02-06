<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\RabbitMqTransport;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message as BunnyMessage;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\ThrowableFormatter;
use Desperado\Domain\Transport\Context\OutboundMessageContextInterface;
use Desperado\Domain\Transport\Message\Message;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Domain\ParameterBag;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContext;
use Desperado\ServiceBus\Transport\IncomingMessageContainer;
use Desperado\ServiceBus\Transport\TransportInterface;
use EventLoop\EventLoop;
use Psr\Log\LoggerInterface;
use function React\Promise\all;
use React\Promise\PromiseInterface;

/**
 * RabbitMQ transport implementation
 */
class RabbitMqTransport implements TransportInterface
{
    /**
     * Transport configuration
     *
     * @var RabbitMqTransportConfig
     */
    private $configuration;

    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Bunny client
     *
     * @var Client
     */
    private $client;

    /**
     * Subscriber
     *
     * @var RabbitMqConsumer
     */
    private $subscriber;

    /**
     * Publisher
     *
     * @var RabbitMqPublisher
     */
    private $publisher;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param RabbitMqTransportConfig    $configuration
     * @param Environment                $environment
     * @param MessageSerializerInterface $messageSerializer
     * @param LoggerInterface            $logger
     */
    public function __construct(
        RabbitMqTransportConfig $configuration,
        Environment $environment,
        MessageSerializerInterface $messageSerializer,
        LoggerInterface $logger
    )
    {
        $this->configuration = $configuration;
        $this->environment = $environment;
        $this->messageSerializer = $messageSerializer;
        $this->logger = $logger;

        \pcntl_async_signals(true);

        \pcntl_signal(\SIGINT, [$this, 'disconnect']);
        \pcntl_signal(\SIGTERM, [$this, 'disconnect']);
    }

    /**
     * Close connections
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @inheritdoc
     */
    public function listen(string $entryPointName, callable $messageHandler, array $clients = []): void
    {
        $this->subscriber = RabbitMqConsumer::create(
            $this->getClient(),
            $this->configuration,
            $this->logger
        );

        $consumeCallable = $this->createSubscribeCallable($messageHandler);

        $this->subscriber
            ->subscribe($entryPointName, $clients)
            ->then(
                function(RabbitMqChannelData $channelData) use ($consumeCallable)
                {
                    $channelData
                        ->getChannel()
                        ->consume($consumeCallable, $channelData->getQueue());
                },
                function()
                {
                    $this->disconnect();
                }
            );

        EventLoop::getLoop()->run();
    }

    /**
     * @inheritdoc
     */
    public function send(Message $message): PromiseInterface
    {
        throw new \LogicException('Method is not used');
    }

    /**
     * @inheritdoc
     */
    public function disconnect(): PromiseInterface
    {
        unset($this->subscriber, $this->client, $this->subscriber, $this->publisher);

        EventLoop::getLoop()->stop();

        exit(0);
    }

    /**
     * @inheritdoc
     */
    public function getMessageSerializer(): MessageSerializerInterface
    {
        return $this->messageSerializer;
    }

    /**
     * Create queue subscriber
     *
     * @param callable $applicationHandler
     *
     * @return callable
     */
    private function createSubscribeCallable(callable $applicationHandler): callable
    {
        return function(BunnyMessage $incoming, Channel $channel) use ($applicationHandler)
        {
            EventLoop::getLoop()->futureTick(
                function() use ($incoming, $channel, $applicationHandler)
                {
                    $receivedMessage = Message::create(
                        $incoming->content,
                        new ParameterBag($incoming->headers),
                        $incoming->exchange,
                        $incoming->routingKey
                    );

                    $incomingContext = RabbitMqIncomingContext::create($receivedMessage, $channel);
                    $outboundContext = OutboundMessageContext::fromIncoming($incomingContext, $this->messageSerializer);

                    $receivedMessageContainer = IncomingMessageContainer::new(
                        $receivedMessage,
                        $incomingContext,
                        $outboundContext
                    );

                    if(true === $this->environment->isDebug())
                    {
                        $this->logger->debug(\sprintf('Received the message: %s', $incoming->content));
                    }

                    $this->handleMessage(
                        $applicationHandler,
                        $receivedMessageContainer,
                        $incoming,
                        $channel
                    );
                }
            );
        };
    }

    /**
     * Handle message
     *
     * @param callable                 $applicationHandler
     * @param IncomingMessageContainer $receivedMessageContainer
     * @param BunnyMessage             $incoming
     * @param Channel                  $channel
     *
     * @return void
     */
    private function handleMessage(
        callable $applicationHandler,
        IncomingMessageContainer $receivedMessageContainer,
        BunnyMessage $incoming,
        Channel $channel
    ): void
    {
        $failedHandler = function(\Throwable $throwable) use ($channel, $incoming)
        {
            $this->logger->error(ThrowableFormatter::toString($throwable));

            $throwable instanceof \LogicException
                ? $channel->nack($incoming)
                : $channel->ack($incoming);
        };

        $channel->ack($incoming);

        try
        {
            /** @noinspection PhpParamsInspection */
            $promise = $applicationHandler($receivedMessageContainer);

            /** @var PromiseInterface $promise */

            $promise->then(
                function(OutboundMessageContextInterface $outboundMessageContext = null) use ($channel, $incoming)
                {
                    if(null === $outboundMessageContext)
                    {
                        return;
                    }

                    $promises = \array_map(
                        function(Message $message) use ($channel)
                        {
                            return $this->getPublisher()->publish($channel, $message);
                        },
                        \iterator_to_array($outboundMessageContext->getToPublishMessages())
                    );

                    all($promises)
                        ->then(
                            null,
                            function(\Throwable $throwable)
                            {
                                $this->logger->error(ThrowableFormatter::toString($throwable));
                            }
                        );
                },
                function(\Throwable $throwable) use ($failedHandler)
                {
                    $failedHandler($throwable);
                }
            );
        }
        catch(\Throwable $throwable)
        {
            $failedHandler($throwable);
        }
    }

    /**
     * Get rabbit mq publisher
     *
     * @return RabbitMqPublisher
     */
    private function getPublisher(): RabbitMqPublisher
    {
        if(null === $this->publisher)
        {
            $this->publisher = RabbitMqPublisher::create(
                $this->environment,
                $this->logger
            );
        }

        return $this->publisher;
    }

    /**
     * Get rabbit mq client
     *
     * @return Client
     */
    private function getClient(): Client
    {
        if(null === $this->client)
        {
            $this->client = new Client(
                EventLoop::getLoop(),
                $this->configuration->getConnectionConfig()->all()
            );
        }

        return $this->client;
    }
}
