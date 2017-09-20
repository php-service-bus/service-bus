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

namespace Desperado\Framework\Backend\Queue;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Desperado\Domain\DaemonInterface;
use Desperado\Domain\EntryPointInterface;
use Desperado\Domain\ReceivedMessage;
use Desperado\Framework\Application\ApplicationLogger;
use EventLoop\EventLoop;
use Psr\Log\LogLevel;

/**
 * RabbitMQ subscriber
 */
class RabbitMQDaemon implements DaemonInterface
{
    protected const LOG_CHANNEL_NAME = 'rabbitMq';

    /** Exchange types */
    protected const EXCHANGE_TYPE_DIRECT = 'direct';
    protected const EXCHANGE_TYPE_FANOUT = 'fanout';
    protected const EXCHANGE_TYPE_TOPIC = 'topic';

    /**
     * Subscriber client
     *
     * @var Client
     */
    private $client;

    /**
     * Fail operation handler function(\Throwable $throwable) {}
     *
     * @var callable
     */
    private $failPromiseResultHandler;

    /**
     * @param string $connectionDSN
     */
    public function __construct(string $connectionDSN)
    {
        $this->initSignals();
        $this->initFailHandler();

        $this->client = new Client(
            EventLoop::getLoop(),
            \parse_url($connectionDSN)
        );
    }

    /**
     * @inheritdoc
     */
    public function run(EntryPointInterface $entryPoint, array $clients = []): void
    {
        $this->connect(
            function(Message $incoming, Channel $channel) use ($entryPoint)
            {
                EventLoop::getLoop()->futureTick(
                    function() use ($incoming, $channel, $entryPoint)
                    {
                        $this->handleMessage($entryPoint, $incoming, $channel);

                        $channel->ack($incoming);
                    }
                );
            },
            $entryPoint,
            $clients
        );

        EventLoop::getLoop()->run();
    }

    /**
     * @inheritdoc
     */
    public function stop(): void
    {
        if(null !== $this->client)
        {
            $callable = function()
            {
                $this->client->stop();
            };

            $this->client
                ->disconnect()
                ->then($callable, $callable);
        }

        ApplicationLogger::info(self::LOG_CHANNEL_NAME, 'RabbitMQ queue daemon stopped');

        exit(0);
    }

    /**
     * Handle received message
     *
     * @param EntryPointInterface $entryPoint
     * @param Message             $incoming
     * @param Channel             $channel
     *
     * @return void
     */
    private function handleMessage(EntryPointInterface $entryPoint, Message $incoming, Channel $channel): void
    {
        try
        {
            $serializer = $entryPoint->getMessageSerializer();

            $context = new RabbitMqDaemonContext($incoming, $channel, $serializer);
            /** @var ReceivedMessage $message */
            $receivedMessage = $serializer->unserialize($incoming->content);

            $entryPoint->handleMessage($receivedMessage->message, $context);
        }
        catch(\Throwable $throwable)
        {
            ApplicationLogger::throwable(self::LOG_CHANNEL_NAME, $throwable);
        }
    }

    /**
     * Init connection fail handler
     *
     * @return void
     */
    private function initFailHandler(): void
    {
        $this->failPromiseResultHandler = function(\Throwable $throwable)
        {
            ApplicationLogger::throwable(self::LOG_CHANNEL_NAME, $throwable, LogLevel::CRITICAL);
        };
    }

    /**
     * Init unix signals
     *
     * @return void
     */
    private function initSignals(): void
    {
        \pcntl_signal(\SIGINT, [$this, 'stop']);
        \pcntl_signal(\SIGTERM, [$this, 'stop']);

        \pcntl_async_signals(true);
    }

    /**
     * Connect to broker
     *
     * @param callable            $consumeCallable
     * @param EntryPointInterface $entryPoint
     * @param array               $clients
     *
     * @return void
     */
    private function connect(callable $consumeCallable, EntryPointInterface $entryPoint, array $clients)
    {
        $this->client
            ->connect()
            ->then(
                function(Client $client) use ($consumeCallable, $entryPoint, $clients)
                {
                    return $client
                        ->channel()
                        ->then(
                            function(Channel $channel) use ($consumeCallable, $entryPoint, $clients)
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
                                        $this->failPromiseResultHandler
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
                                        $this->failPromiseResultHandler
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
                                        $this->failPromiseResultHandler
                                    )
                                    ->then(
                                        function(MethodQueueDeclareOkFrame $frame) use ($channel, $consumeCallable)
                                        {
                                            ApplicationLogger::info(
                                                self::LOG_CHANNEL_NAME,
                                                'RabbitMQ daemon started'
                                            );

                                            return $channel->consume(
                                                $consumeCallable,
                                                $frame->queue
                                            );
                                        },
                                        $this->failPromiseResultHandler
                                    );
                            },
                            $this->failPromiseResultHandler
                        );
                },
                $this->failPromiseResultHandler
            );
    }
}
