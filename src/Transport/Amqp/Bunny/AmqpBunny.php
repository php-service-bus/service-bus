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

namespace Desperado\ServiceBus\Transport\Amqp\Bunny;

use function Amp\call;
use Amp\Promise;
use function Amp\Promise\wait;
use Desperado\ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use Desperado\ServiceBus\Transport\Amqp\AmqpQoSConfiguration;
use Desperado\ServiceBus\Transport\Consumer;
use Desperado\ServiceBus\Transport\Exceptions\ConnectionFail;
use Desperado\ServiceBus\Transport\Marshal\Decoder\JsonMessageDecoder;
use Desperado\ServiceBus\Transport\Marshal\Decoder\TransportMessageDecoder;
use Desperado\ServiceBus\Transport\Marshal\Encoder\JsonMessageEncoder;
use Desperado\ServiceBus\Transport\Marshal\Encoder\TransportMessageEncoder;
use Desperado\ServiceBus\Transport\Publisher;
use Desperado\ServiceBus\Transport\Queue;
use Desperado\ServiceBus\Transport\QueueBind;
use Desperado\ServiceBus\Transport\Topic;
use Desperado\ServiceBus\Transport\TopicBind;
use Desperado\ServiceBus\Transport\Transport;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 *
 */
final class AmqpBunny implements Transport
{
    /**
     * @var AmqpBunnyClient
     */
    private $client;

    /**
     * @var AmqpBunnyChannel
     */
    private $channel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TransportMessageDecoder
     */
    private $messageDecoder;

    /**
     * @var TransportMessageEncoder
     */
    private $messageEncoder;

    /**
     * @var AmqpBunnyChannelConfigurator
     */
    private $channelConfigurator;

    /**
     * @param AmqpConnectionConfiguration  $amqpConfiguration
     * @param TransportMessageEncoder|null $messageEncoder
     * @param TransportMessageDecoder|null $messageDecoder
     * @param LoggerInterface|null         $logger
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     */
    public function __construct(
        AmqpConnectionConfiguration $amqpConfiguration,
        AmqpQoSConfiguration $amqpQoSConfiguration = null,
        TransportMessageEncoder $messageEncoder = null,
        TransportMessageDecoder $messageDecoder = null,
        LoggerInterface $logger = null
    )
    {
        $this->messageEncoder = $messageEncoder ?? new JsonMessageEncoder();
        $this->messageDecoder = $messageDecoder ?? new JsonMessageDecoder();
        $this->logger         = $logger ?? new NullLogger();

        $this->client = new AmqpBunnyClient($amqpConfiguration, $logger);

        $this->connectImmediately(
            $amqpQoSConfiguration ?? new AmqpQoSConfiguration()
        );

        $this->channelConfigurator = new AmqpBunnyChannelConfigurator($this->channel, $this->logger);
    }

    /**
     * @psalm-suppress TypeCoercion
     *
     * @inheritDoc
     */
    public function createTopic(Topic $topic): void
    {
        /** @var \Desperado\ServiceBus\Transport\Amqp\AmqpExchange $topic */

        $this->channelConfigurator->addExchange($topic);
    }

    /**
     * @inheritDoc
     */
    public function bindTopic(TopicBind $to): void
    {
        $this->channelConfigurator->bindTopic(
            (string) $to->sourceTopic(),
            (string) $to->destinationTopic(),
            (string) $to->routingKey()
        );
    }

    /**
     * @psalm-suppress TypeCoercion
     *
     * @inheritDoc
     */
    public function createQueue(Queue $queue): void
    {
        /** @var \Desperado\ServiceBus\Transport\Amqp\AmqpQueue $queue */

        $this->channelConfigurator->addQueue($queue);
    }

    /**
     * @inheritDoc
     */
    public function bindQueue(QueueBind $to): void
    {
        $this->channelConfigurator->bindQueue(
            (string) $to->queue(),
            (string) $to->topic(),
            (string) $to->routingKey()
        );
    }

    /**
     * @inheritDoc
     */
    public function createPublisher(): Publisher
    {
        return new BunnyPublisher(
            $this->channel,
            $this->messageEncoder
        );
    }

    /**
     * @inheritDoc
     */
    public function createConsumer(Queue $listenQueue): Consumer
    {
        /** @var \Desperado\ServiceBus\Transport\Amqp\AmqpQueue $listenQueue */

        return new AmqpBunnyConsumer(
            $listenQueue,
            $this->channel,
            $this->messageDecoder,
            $this->logger
        );
    }

    /**
     * @inheritDoc
     */
    public function close(): Promise
    {
        return $this->client->disconnect();
    }

    /**
     * @param AmqpQoSConfiguration $amqpQoSConfiguration
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     */
    private function connectImmediately(AmqpQoSConfiguration $amqpQoSConfiguration): void
    {
        try
        {
            /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
            $promise = call(
                function(AmqpQoSConfiguration $amqpQoSConfiguration): \Generator
                {
                    yield $this->client->connect();

                    /** @var AmqpBunnyChannel $channel */
                    $channel = yield $this->client->channel();

                    $this->channel = $channel;

                    yield $channel->qos(
                        $amqpQoSConfiguration->qosSize(),
                        $amqpQoSConfiguration->qosCount(),
                        $amqpQoSConfiguration->isGlobal()
                    );
                },
                $amqpQoSConfiguration
            );

            /** force promise resolve */
            wait($promise);
        }
        catch(\Throwable $throwable)
        {
            /** Delete message from promise resolver (wait()) */
            if(null !== $throwable->getPrevious())
            {
                /** @var \Throwable $throwable */
                $throwable = $throwable->getPrevious();
            }

            throw new ConnectionFail($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }
}
