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

namespace Desperado\ServiceBus\Transport\AmqpExt;

use Desperado\ServiceBus\Transport\Consumer;
use Desperado\ServiceBus\Transport\Exceptions\ConnectionFail;
use Desperado\ServiceBus\Transport\Exceptions\CreateQueueFailed;
use Desperado\ServiceBus\Transport\Exceptions\CreateTopicFailed;
use Desperado\ServiceBus\Transport\Exceptions\NotConfiguredQueue;
use Desperado\ServiceBus\Transport\Marshal\Decoder\TransportMessageDecoder;
use Desperado\ServiceBus\Transport\Marshal\Encoder\TransportMessageEncoder;
use Desperado\ServiceBus\Transport\Publisher;
use Desperado\ServiceBus\Transport\Queue;
use Desperado\ServiceBus\Transport\QueueBind;
use Desperado\ServiceBus\Transport\Topic;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Amqp-ext based transport implementation
 */
final class AmqpExt
{
    /**
     * Represents a AMQP connection between PHP and a AMQP server
     *
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * Represents a AMQP channel between PHP and a AMQP server
     *
     * @var \AMQPChannel
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
     * Amqp exchanges
     *
     * @var array<string, \AMQPExchange>
     */
    private $exchanges = [];

    /**
     * Amqp queues
     *
     * @var array<string, \AMQPQueue>
     */
    private $queues = [];

    /**
     * Relation exchange -> routing keys
     *
     * @var array<string, string[]>
     */
    private $exchangeBinds = [];

    /**
     * @param AmqpConfiguration       $amqpConfiguration
     * @param TransportMessageEncoder $messageEncoder
     * @param TransportMessageDecoder $messageDecoder
     * @param LoggerInterface|null    $logger
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     */
    public function __construct(
        AmqpConfiguration $amqpConfiguration,
        TransportMessageEncoder $messageEncoder,
        TransportMessageDecoder $messageDecoder,
        LoggerInterface $logger = null
    )
    {
        try
        {
            $this->messageEncoder = $messageEncoder;
            $this->messageDecoder = $messageDecoder;
            $this->logger         = $logger ?? new NullLogger();

            $this->connection = self::createConnection($amqpConfiguration);
            $this->connection->pconnect();

            $this->channel = self::createChannel($this->connection);
        }
        catch(\Exception $exception)
        {
            throw new ConnectionFail($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function __destruct()
    {
        try
        {
            if(true === $this->connection->isConnected())
            {
                $this->connection->pdisconnect();
            }
        }
        catch(\Throwable $throwable)
        {

        }
    }

    /**
     * @inheritdoc
     */
    public function createTopic(Topic $topic): void
    {
        try
        {
            /** @var AmqpTopic $topic */

            $exchange = new \AMQPExchange($this->channel);

            $exchange->setName((string) $topic);
            $exchange->setType($topic->type());
            $exchange->setFlags($topic->flags());
            $exchange->setArguments($topic->arguments());
            $exchange->declareExchange();

            $this->exchanges[(string) $topic] = $exchange;
        }
        catch(\AMQPConnectionException $exception)
        {
            throw new ConnectionFail($exception->getMessage(), $exception->getCode(), $exception);
        }
        catch(\Throwable $throwable)
        {
            throw new CreateTopicFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function createQueue(Queue $queue, QueueBind $bind = null): void
    {
        try
        {
            /** @var AmqpQueue $queue */

            $amqpQueue = new \AMQPQueue($this->channel);
            $amqpQueue->setName((string) $queue);
            $amqpQueue->setFlags($queue->flags());
            $amqpQueue->setArguments($queue->arguments());

            $amqpQueue->declareQueue();

            if(null !== $bind && null !== $bind->routingKey() && '' !== (string) $bind->routingKey())
            {
                $routingKey = (string) $bind->routingKey();

                /** @psalm-suppress InvalidPropertyAssignmentValue */
                $this->exchangeBinds[(string) $bind->topic()][] = $routingKey;

                $amqpQueue->bind((string) $bind->topic(), $routingKey);
            }

            /** @psalm-suppress InvalidArrayAssignment */
            $this->queues[(string) $queue] = $amqpQueue;
        }
        catch(\AMQPConnectionException $exception)
        {
            throw new ConnectionFail($exception->getMessage(), $exception->getCode(), $exception);
        }
        catch(\Throwable $throwable)
        {
            throw new CreateQueueFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function createPublisher(): Publisher
    {
        return new AmqpPublisher($this->exchanges, $this->messageEncoder);
    }

    /**
     * @inheritdoc
     */
    public function createConsumer(Queue $listenQueue): Consumer
    {
        $queue = $this->extractAmqpQueue((string) $listenQueue);

        if(null !== $queue)
        {
            return new AmqpConsumer(
                $queue,
                $this->messageDecoder,
                $this->logger
            );
        }

        throw new NotConfiguredQueue(
            \sprintf('Queue "%s" was not configured. Please use createQueue method', $listenQueue)
        );
    }

    /**
     * @inheritdoc
     */
    public function close(): void
    {
        try
        {
            $this->connection->disconnect();
        }
        catch(\Throwable $throwable)
        {

        }
    }

    /**
     * @param string $listenQueue
     *
     * @return \AMQPQueue|null
     */
    private function extractAmqpQueue(string $listenQueue): ?\AMQPQueue
    {
        return true === isset($this->queues[$listenQueue])
            ? $this->queues[$listenQueue]
            : null;

    }

    /**
     * @param AmqpConfiguration $amqpConfiguration
     *
     * @return \AMQPConnection
     */
    private static function createConnection(AmqpConfiguration $amqpConfiguration): \AMQPConnection
    {
        return new \AMQPConnection([
            'host'            => $amqpConfiguration->host(),
            'port'            => $amqpConfiguration->port(),
            'vhost'           => $amqpConfiguration->virtualHost(),
            'login'           => $amqpConfiguration->user(),
            'password'        => $amqpConfiguration->password(),
            'connect_timeout' => $amqpConfiguration->timeout()
        ]);
    }

    /**
     * @param \AMQPConnection $connection
     *
     * @return \AMQPChannel
     * @throws \AMQPConnectionException
     */
    private static function createChannel(\AMQPConnection $connection): \AMQPChannel
    {
        return new \AMQPChannel($connection);
    }
}
