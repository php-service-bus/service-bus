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
use function React\Promise\all;
use React\Promise\PromiseInterface;
use React\Promise\RejectedPromise;

/**
 * RabbitMQ subscriber
 */
final class RabbitMqConsumer
{
    public const HEADER_DELIVERY_MODE_KEY = 'delivery-mode';
    public const HEADER_DELAY_KEY = 'x-delay';

    public const NON_PERSISTED_DELIVERY_MODE = 1;
    public const PERSISTED_DELIVERY_MODE = 2;

    private const EXCHANGE_TYPE_DIRECT = 'direct';
    private const EXCHANGE_TYPE_FANOUT = 'fanout';

    /** Plugin rabbitmq_delayed_message_exchange must be enabled */
    private const EXCHANGE_TYPE_DELAYED = 'x-delayed-message';

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
     * @return self
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
     *
     * @return PromiseInterface
     */
    public function subscribe(string $entryPointName): PromiseInterface
    {
        return $this
            ->doConnect()
            ->then(
                function(Channel $channel)
                {
                    return $this->doConfigureChannel($channel);
                }
            )
            ->then(
                function(Channel $channel) use ($entryPointName)
                {
                    return $this->doConfigureExchanges($channel, $entryPointName);
                }
            )
            ->then(
                function(array $arguments)
                {
                    [$frame, $channel] = $arguments;

                    /** @var MethodQueueDeclareOkFrame $frame */
                    /** @var Channel $channel */

                    $this->logger->info('RabbitMQ subscription started');

                    return RabbitMqChannelData::create($channel, $frame->queue);
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

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            return new RejectedPromise($throwable);
        };
    }

    /**
     * Connects to AMQP server
     *
     * @return PromiseInterface
     */
    private function doConnect(): PromiseInterface
    {
        return $this->client
            ->connect()
            ->then(
                function(Client $client)
                {
                    return $client->channel();
                }
            );
    }

    /**
     * Calls basic.qos AMQP method
     *
     * @param Channel $channel
     *
     * @return PromiseInterface
     */
    private function doConfigureChannel(Channel $channel): PromiseInterface
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
                }
            );
    }

    /**
     * Calls exchange.declare AMQP method
     * Calls queue.declare AMQP method
     *
     * @param Channel $channel
     * @param string  $entryPointName
     *
     * @return PromiseInterface
     */
    private function doConfigureExchanges(Channel $channel, string $entryPointName): PromiseInterface
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
                }
            )
            /** Scheduler exchange */
            ->then(
                function() use ($channel, $entryPointName)
                {
                    return $channel->exchangeDeclare(
                        \sprintf('%s.timeout', $entryPointName),
                        self::EXCHANGE_TYPE_DELAYED,
                        false,
                        false,
                        false,
                        false,
                        false,
                        ['x-delayed-type' => self::EXCHANGE_TYPE_FANOUT]
                    );
                }
            )
            /** x-dead-letter-exchange queue */
            ->then(
                function() use ($channel, $entryPointName)
                {
                    return $channel->queueDeclare(
                        \sprintf('%s.timeout', $entryPointName),
                        false,
                        true,
                        false,
                        false,
                        false, ['x-dead-letter-exchange' => $entryPointName]);
                }
            )
            /** Messages (internal usage) queue */
            ->then(
                function() use ($channel, $entryPointName)
                {
                    return $channel->queueDeclare(
                        \sprintf('%s.messages', $entryPointName),
                        false, true
                    );
                }
            )
            ->then(
                function(MethodQueueDeclareOkFrame $frame) use ($channel, $entryPointName)
                {
                    return $channel
                        ->queueBind($frame->queue, \sprintf('%s.timeout', $entryPointName))
                        ->then(
                            function() use ($frame)
                            {
                                return $frame;
                            }
                        );
                }
            )
            /** Configure routing keys for clients */
            ->then(
                function(MethodQueueDeclareOkFrame $frame) use ($channel, $entryPointName)
                {
                    return $this->doConfigureRoutingKeys($frame, $channel, $entryPointName);
                }
            );
    }

    /**
     * Configure clients routing keys
     *
     * @param MethodQueueDeclareOkFrame $frame
     * @param Channel                   $channel
     * @param string                    $entryPointName
     *
     * @return PromiseInterface
     */
    private function doConfigureRoutingKeys(
        MethodQueueDeclareOkFrame $frame,
        Channel $channel,
        string $entryPointName
    ): PromiseInterface
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
            [$entryPointName]
        );

        return all($promises)
            ->then(
                function() use ($frame, $channel)
                {
                    return [$frame, $channel];
                }
            );
    }
}
