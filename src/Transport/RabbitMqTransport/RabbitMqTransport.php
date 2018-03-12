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
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\ThrowableFormatter;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Domain\ParameterBag;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContext;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContextInterface;
use Desperado\ServiceBus\Transport\IncomingMessageContainer;
use Desperado\ServiceBus\Transport\Message\Message;
use Desperado\ServiceBus\Transport\TransportInterface;
use EventLoop\EventLoop;
use Psr\Log\LoggerInterface;
use function React\Promise\all;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 * RabbitMQ transport implementation
 */
final class RabbitMqTransport implements TransportInterface
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
        $this->configuration     = $configuration;
        $this->environment       = $environment;
        $this->messageSerializer = $messageSerializer;
        $this->logger            = $logger;

        \pcntl_async_signals(true);

        \pcntl_signal(\SIGINT, [$this, 'disconnect']);
        \pcntl_signal(\SIGTERM, [$this, 'disconnect']);
    }

    /**
     * Close connections
     *
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @inheritdoc
     */
    public function listen(string $entryPointName, callable $messageHandler): void
    {
        $this->subscriber = RabbitMqConsumer::create(
            $this->getClient(),
            $this->configuration,
            $this->logger
        );

        $consumeCallable = $this->createSubscribeCallable($entryPointName, $messageHandler);

        $this->subscriber
            ->subscribe($entryPointName)
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

        return new FulfilledPromise();
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
     * @param string   $entryPointName
     * @param callable $applicationHandler
     *
     * @return callable
     */
    private function createSubscribeCallable(string $entryPointName, callable $applicationHandler): callable
    {
        return function(BunnyMessage $incoming, Channel $channel) use ($applicationHandler, $entryPointName)
        {
            EventLoop::getLoop()->futureTick(
                function() use ($incoming, $channel, $applicationHandler, $entryPointName)
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
                        $entryPointName,
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
     * @param string                   $entryPointName
     * @param callable                 $applicationHandler
     * @param IncomingMessageContainer $receivedMessageContainer
     * @param BunnyMessage             $incoming
     * @param Channel                  $channel
     *
     * @return void
     */
    private function handleMessage(
        string $entryPointName,
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
                function(array $contexts = null) use ($channel, $entryPointName)
                {
                    if(false === \is_array($contexts) || 0 === \count($contexts))
                    {
                        return;
                    }

                    $promises = [];

                    foreach($contexts as $context)
                    {
                        /** @var OutboundMessageContextInterface $context */

                        foreach(\iterator_to_array($context->getToPublishMessages()) as $message)
                        {
                            /** @var Message $message */

                            $promises[] = $this->getPublisher()->publish(
                                $channel, $message, RabbitMqConsumer::EXCHANGE_TYPE_DIRECT
                            );

                            if(true === $message->isEvent())
                            {
                                $promises[] = $this->getPublisher()->publish(
                                    $channel,
                                    $message->changeExchange(\sprintf('%s.events', $entryPointName)),
                                    RabbitMqConsumer::EXCHANGE_TYPE_DIRECT
                                );
                            }
                        }
                    }

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
