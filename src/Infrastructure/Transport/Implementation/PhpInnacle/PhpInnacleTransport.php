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

namespace Desperado\ServiceBus\Infrastructure\Transport\Implementation\PhpInnacle;

use function Amp\call;
use Amp\Emitter;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\Transport\Exceptions\ConnectionFail;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpConnectionConfiguration;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpExchange;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQoSConfiguration;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQueue;
use Desperado\ServiceBus\Infrastructure\Transport\Package\OutboundPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Queue;
use Desperado\ServiceBus\Infrastructure\Transport\QueueBind;
use Desperado\ServiceBus\Infrastructure\Transport\Topic;
use Desperado\ServiceBus\Infrastructure\Transport\TopicBind;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;
use PHPinnacle\Ridge\Channel;
use PHPinnacle\Ridge\Client;
use PHPinnacle\Ridge\Config;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 *
 */
final class PhpInnacleTransport implements Transport
{
    /**
     * Client for work with AMQP protocol
     *
     * @var Client
     */
    private $client;

    /**
     * Channel client
     *
     * Null if not connected
     *
     * @var Channel|null
     */
    private $channel;

    /**
     * Publisher
     *
     * @var PhpInnaclePublisher|null
     */
    private $publisher;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array<string, \Desperado\ServiceBus\Infrastructure\Transport\Implementation\PhpInnacle\PhpInnacleConsumer>
     */
    private $consumers = [];

    /**
     * @param AmqpConnectionConfiguration $connectionConfig
     * @param AmqpQoSConfiguration|null   $qosConfig
     * @param LoggerInterface|null        $logger
     */
    public function __construct(
        AmqpConnectionConfiguration $connectionConfig,
        AmqpQoSConfiguration $qosConfig = null,
        ?LoggerInterface $logger = null
    )
    {
        $qosConfig = $qosConfig ?? new AmqpQoSConfiguration();

        $this->logger = $logger ?? new NullLogger();
        $this->client = new Client($this->adaptConfig($connectionConfig, $qosConfig));
    }

    /**
     * @inheritDoc
     */
    public function connect(): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(): \Generator
            {
                if(true === $this->client->isConnected())
                {
                    return;
                }

                try
                {
                    yield $this->client->connect();

                    /** @var Channel $channel */
                    $channel = yield $this->client->channel();

                    $this->channel = $channel;

                    unset($channel);

                    $this->logger->info('Connected to broker');
                }
                catch(\Throwable $throwable)
                {
                    throw new ConnectionFail($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(): \Generator
            {
                try
                {
                    if(true === $this->client->isConnected())
                    {
                        yield $this->client->disconnect();
                    }
                }
                catch(\Throwable $throwable)
                {
                    /** Not interested */
                }
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function consume(Queue $queue): Promise
    {
        /** @var AmqpQueue $queue */

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(AmqpQueue $queue): \Generator
            {
                yield $this->connect();

                /** @var Channel $channel */
                $channel  = $this->channel;
                $emitter  = new Emitter();
                $consumer = new PhpInnacleConsumer($queue, $channel, $this->logger);

                $consumer->listen(
                    static function(PhpInnacleIncomingPackage $incomingPackage) use ($emitter): \Generator
                    {
                        yield $emitter->emit($incomingPackage);
                    }
                );

                $this->consumers[(string) $queue] = $consumer;

                unset($consumer, $channel);

                return $emitter->iterate();
            },
            $queue
        );
    }

    /**
     * @inheritDoc
     */
    public function stop(Queue $queue): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(Queue $queue): \Generator
            {
                $queueName = (string) $queue;

                if(true === isset($this->consumers[$queueName]))
                {
                    /** @var PhpInnacleConsumer $consumer */
                    $consumer = $this->consumers[$queueName];

                    yield $consumer->stop();

                    unset($consumer, $this->consumers[$queueName]);
                }
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function send(OutboundPackage $outboundPackage): Promise
    {
        return call(
            function(OutboundPackage $outboundPackage): \Generator
            {
                yield $this->connect();

                /** @var Channel $channel */
                $channel = $this->channel;

                if(null === $this->publisher)
                {
                    $this->publisher = new PhpInnaclePublisher($channel, $this->logger);
                }

                yield $this->publisher->process($outboundPackage);
            },
            $outboundPackage
        );
    }

    /**
     * @inheritDoc
     */
    public function createTopic(Topic $topic, TopicBind ...$binds): Promise
    {
        /** @var AmqpExchange $topic */

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(AmqpExchange $exchange, array $binds): \Generator
            {
                /** @var array<mixed, \Desperado\ServiceBus\Infrastructure\Transport\TopicBind> $binds */

                yield $this->connect();

                /** @var Channel $channel */
                $channel = $this->channel;

                $configurator = new PhpInnacleConfigurator($channel);

                yield from $configurator->doCreateExchange($exchange);
                yield from $configurator->doBindExchange($exchange, $binds);

                unset($channel, $configurator);
            },
            $topic, $binds
        );
    }

    /**
     * @inheritDoc
     */
    public function createQueue(Queue $queue, QueueBind ...$binds): Promise
    {
        /** @var AmqpQueue $queue */

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(AmqpQueue $queue, array $binds): \Generator
            {
                /** @var array<mixed, \Desperado\ServiceBus\Infrastructure\Transport\QueueBind> $binds */

                yield $this->connect();

                /** @var Channel $channel */
                $channel = $this->channel;

                $configurator = new PhpInnacleConfigurator($channel);

                yield from $configurator->doCreateQueue($queue);
                yield from $configurator->doBindQueue($queue, $binds);

                unset($channel, $configurator);
            },
            $queue, $binds
        );
    }

    /**
     * Create phpinnacle configuration
     *
     * @return Config
     */
    private function adaptConfig(
        AmqpConnectionConfiguration $connectionConfiguration,
        AmqpQoSConfiguration $qoSConfiguration
    ): Config
    {
        $config = new Config(
            $connectionConfiguration->host(),
            $connectionConfiguration->port(),
            $connectionConfiguration->virtualHost(),
            $connectionConfiguration->user(),
            $connectionConfiguration->password()
        );

        $config->heartbeat((int) $connectionConfiguration->heartbeatInterval());
        $config->qosCount($qoSConfiguration->qosCount());
        $config->qosSize($qoSConfiguration->qosSize());
        $config->qosGlobal($qoSConfiguration->isGlobal());

        return $config;
    }
}
