<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Backend\ReactRabbitMq;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Desperado\Domain\EntryPoint\EntryPointInterface;
use Desperado\Domain\ThrowableFormatter;
use React\Promise\PromiseInterface;
use Psr\Log\LoggerInterface;
use React\Promise\RejectedPromise;

/**
 * Consumer
 */
class ReactRabbitMqConsumer
{
    protected const EXCHANGE_TYPE_DIRECT = 'direct';
    protected const EXCHANGE_TYPE_FANOUT = 'fanout';
    protected const EXCHANGE_TYPE_TOPIC = 'topic';

    /**
     * Rabbit mq configuration
     *
     * @var ReactRabbitMqConfiguration
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
     * @param Client                     $client
     * @param ReactRabbitMqConfiguration $configuration
     * @param LoggerInterface            $logger
     */
    public function __construct(Client $client, ReactRabbitMqConfiguration $configuration, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * Configure queue and start subscribe
     *
     * @param EntryPointInterface $entryPoint
     * @param array               $clients
     *
     * @return PromiseInterface
     */
    public function subscribe(EntryPointInterface $entryPoint, array $clients): PromiseInterface
    {
        $onFailed = function(\Throwable $throwable)
        {
            $this->logger->critical(ThrowableFormatter::toString($throwable));

            return new RejectedPromise($throwable);
        };

        return $this->client
            ->connect()
            ->then(
                function(Client $client) use ($onFailed, $entryPoint, $clients)
                {
                    return $client
                        ->channel()
                        ->then(
                            function(Channel $channel) use ($onFailed)
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
                                        $onFailed
                                    );
                            },
                            $onFailed
                        )
                        ->then(
                            function(Channel $channel) use ($onFailed, $entryPoint, $clients)
                            {
                                $entryPointName = $entryPoint->getEntryPointName();

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
                                        $onFailed
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
                                        $onFailed
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
                                        $onFailed
                                    )
                                    ->then(
                                        function(MethodQueueDeclareOkFrame $frame) use ($channel)
                                        {
                                            $this->logger->info('RabbitMQ daemon started');

                                            return new ReactRabbitMqChannelData($channel, $frame->queue);
                                        },
                                        $onFailed
                                    );
                            },
                            $onFailed
                        );
                },
                $onFailed
            );
    }

    /**
     * Stop subscriber
     *
     * @return void
     */
    public function unsubcribe(): void
    {
        if(null !== $this->client)
        {
            $callable = function()
            {
                $this->client->stop();

                $this->logger->info('RabbitMQ queue daemon stopped');
            };

            $this->client
                ->disconnect()
                ->then($callable, $callable);
        }
    }
}
