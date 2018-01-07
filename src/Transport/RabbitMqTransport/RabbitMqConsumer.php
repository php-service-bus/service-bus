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
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Desperado\Domain\ThrowableFormatter;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use React\Promise\RejectedPromise;

/**
 * RabbitMQ subscriber
 */
class RabbitMqConsumer
{
    protected const EXCHANGE_TYPE_DIRECT = 'direct';
    protected const EXCHANGE_TYPE_FANOUT = 'fanout';
    protected const EXCHANGE_TYPE_TOPIC = 'topic';

    /**
     * Rabbit mq configuration
     *
     * @var RabbitMqTransportConfig
     */
    private $configuration;

    /**
     * Bunny client
     *
     * @var Client
     */
    private $client;

    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Anonymous function that will be called to handle exceptions
     *
     * @var callable
     */
    private $onFailedCallable;

    /**
     * Create consumer instance
     *
     * @param Client                  $client
     * @param RabbitMqTransportConfig $configuration
     * @param LoggerInterface         $logger
     *
     * @return RabbitMqConsumer
     */
    public static function create(
        Client $client,
        RabbitMqTransportConfig $configuration,
        LoggerInterface $logger
    ): self
    {
        return new self($client, $configuration, $logger);
    }

    /**
     * Configure queue and start subscribe
     *
     * @param string $entryPointName
     * @param array  $clients
     *
     * @return PromiseInterface
     */
    public function subscribe(string $entryPointName, array $clients): PromiseInterface
    {
        return $this->client
            ->connect()
            ->then(
                function(Client $client) use ($entryPointName, $clients)
                {
                    return $client
                        ->channel()
                        ->then(
                            function(Channel $channel)
                            {
                                return $channel
                                    ->qos(
                                        $this->configuration->getQosConfig()->get('pre_fetch_size'),
                                        $this->configuration->getQosConfig()->get('pre_fetch_count'),
                                        $this->configuration->getQosConfig()->get('global')
                                    )
                                    ->then(
                                        function() use ($channel)
                                        {
                                            return $channel;
                                        },
                                        $this->onFailedCallable
                                    );
                            },
                            $this->onFailedCallable
                        )
                        ->then(
                            function(Channel $channel) use ($entryPointName, $clients)
                            {
                                return $channel
                                    /** Application main exchange */
                                    ->exchangeDeclare($entryPointName, self::EXCHANGE_TYPE_DIRECT)
                                    /** Events exchanges */
                                    ->then(
                                        function() use ($channel, $entryPointName)
                                        {
                                            return $channel
                                                ->exchangeDeclare(
                                                    \sprintf('%s.events', $entryPointName),
                                                    self::EXCHANGE_TYPE_DIRECT
                                                );
                                        },
                                        $this->onFailedCallable
                                    )
                                    /** Messages (internal usage) queue */
                                    ->then(
                                        function() use ($channel, $entryPointName)
                                        {
                                            return $channel->queueDeclare(
                                                \sprintf('%s.messages', $entryPointName),
                                                false, true
                                            );
                                        },
                                        $this->onFailedCallable
                                    )
                                    /** Configure routing keys for clients */
                                    ->then(
                                        function(MethodQueueDeclareOkFrame $frame) use (
                                            $channel, $clients, $entryPointName
                                        )
                                        {
                                            $promises = \array_map(
                                                function($routingKey) use ($frame, $channel, $entryPointName)
                                                {
                                                    return $channel->queueBind(
                                                        $frame->queue,
                                                        $entryPointName,
                                                        $routingKey
                                                    );
                                                },
                                                $clients
                                            );

                                            return \React\Promise\all($promises)
                                                ->then(
                                                    function() use ($frame)
                                                    {
                                                        return $frame;
                                                    }
                                                );
                                        },
                                        $this->onFailedCallable
                                    )
                                    ->then(
                                        function(MethodQueueDeclareOkFrame $frame) use ($channel)
                                        {
                                            $this->logger->info('RabbitMQ subscription started');

                                            return RabbitMqChannelData::create($channel, $frame->queue);
                                        },
                                        $this->onFailedCallable
                                    );
                            },
                            $this->onFailedCallable
                        );
                },
                $this->onFailedCallable
            );
    }

    /**
     * Close subscription
     */
    public function __destruct()
    {
        if(null !== $this->client)
        {
            $callable = function()
            {
                $this->client->stop();

                $this->logger->info('RabbitMQ subscription stopped');
            };

            $this->client
                ->disconnect()
                ->then($callable, $callable);
        }
    }

    /**
     * @param Client                  $client
     * @param RabbitMqTransportConfig $configuration
     * @param LoggerInterface         $logger
     */
    private function __construct(Client $client, RabbitMqTransportConfig $configuration, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->configuration = $configuration;
        $this->logger = $logger;

        $this->onFailedCallable = function(\Throwable $throwable) use ($logger)
        {
            $logger->critical(ThrowableFormatter::toString($throwable));

            return new RejectedPromise($throwable);
        };
    }
}
