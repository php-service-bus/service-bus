<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\Backend\RabbitMQ;

use Bunny\Channel;
use Bunny\Message;
use Bunny\Async;
use Desperado\ConcurrencyFramework\Domain\Application\BackendInterface;
use Desperado\ConcurrencyFramework\Domain\Application\KernelInterface;
use Psr\Log\LoggerInterface;
use EventLoop\EventLoop;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Desperado\ConcurrencyFramework\Common\Formatter\ThrowableFormatter;
use Desperado\ConcurrencyFramework\Domain\Messages\ReceivedMessage;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;

/**
 * ReactPHP rabbit mq client
 */
class RabbitMqBackend implements BackendInterface
{
    /** Exchange types */
    protected const EXCHANGE_TYPE_DIRECT = 'direct';
    protected const EXCHANGE_TYPE_FANOUT = 'fanout';
    protected const EXCHANGE_TYPE_TOPIC = 'topic';


    /**
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Messages serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPoint;

    /**
     * ReactPHP event loop
     *
     * @var \React\EventLoop\LoopInterface
     */
    private $eventLoop;

    /**
     * DSN parts
     *
     * @var array
     */
    private $connectionDsnParts;

    /**
     * Subscriber client
     *
     * @var Async\Client
     */
    private $client;

    /**
     * Failed promise handler
     *
     * @var callable
     */
    private $failPromiseResultHandler;

    /**
     * @param string                     $connectionDSN
     * @param string                     $entryPoint
     * @param LoggerInterface            $logger
     * @param MessageSerializerInterface $messageSerializer
     */
    public function __construct(
        string $connectionDSN,
        string $entryPoint,
        LoggerInterface $logger,
        MessageSerializerInterface $messageSerializer
    )
    {
        $this->connectionDsnParts = \parse_url($connectionDSN);
        $this->entryPoint = $entryPoint;
        $this->messageSerializer = $messageSerializer;
        $this->logger = $logger;

        $this->eventLoop = EventLoop::getLoop();
        $this->client = new Async\Client($this->eventLoop, $this->connectionDsnParts, $logger);

        $this->initSignals();
        $this->initFailHandler();
    }

    /**
     * @inheritdoc
     */
    public function run(KernelInterface $kernel, array $clients): void
    {
        $this->connect(
            function(Message $incoming, Channel $channel) use ($kernel)
            {
                $this->eventLoop->futureTick(
                    function() use ($incoming, $channel, $kernel)
                    {
                        $this->handleMessage($kernel, $incoming, $channel);
                        $channel->ack($incoming);
                    }
                );
            },
            $clients
        );

        $this->eventLoop->run();
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

        $this->logger->debug('RabbitMQ queue daemon stopped');
        exit(0);
    }

    /**
     * Connect to broker
     *
     * @param callable $consumeCallable
     * @param array    $clients
     *
     * @return void
     */
    private function connect(callable $consumeCallable, array $clients)
    {
        $this->client
            ->connect()
            ->then(
                function(Async\Client $client) use ($consumeCallable, $clients)
                {
                    return $client
                        ->channel()
                        ->then(
                            function(Channel $channel) use ($consumeCallable, $clients)
                            {
                                return $channel
                                    /** Application main exchange */
                                    ->exchangeDeclare($this->entryPoint, self::EXCHANGE_TYPE_DIRECT)
                                    /** Events exchanges */
                                    ->then(
                                        function() use ($channel)
                                        {
                                            return $channel
                                                ->exchangeDeclare(
                                                    \sprintf('%s.events', $this->entryPoint),
                                                    self::EXCHANGE_TYPE_DIRECT
                                                );
                                        },
                                        $this->failPromiseResultHandler
                                    )
                                    /** Messages (internal usage) queue */
                                    ->then(
                                        function() use ($channel)
                                        {
                                            return $channel->queueDeclare(
                                                \sprintf('%s.messages', $this->entryPoint),
                                                false, true
                                            );
                                        },
                                        $this->failPromiseResultHandler
                                    )
                                    /** Configure routing keys for clients */
                                    ->then(
                                        function(MethodQueueDeclareOkFrame $frame) use ($channel, $clients)
                                        {
                                            $promises = \array_map(
                                                function($routingKey) use ($frame, $channel)
                                                {
                                                    return $channel->queueBind($frame->queue, $this->entryPoint, $routingKey);
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
                                            $this->logger
                                                ->debug(
                                                    \sprintf(
                                                        'RabbitMQ daemon for the entry point "%s" started',
                                                        $this->entryPoint
                                                    )
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

    /**
     * Handle message
     *
     * @param KernelInterface $kernel
     * @param Message         $incoming
     * @param Channel         $channel
     *
     * @return void
     */
    private function handleMessage(KernelInterface $kernel, Message $incoming, Channel $channel): void
    {
        try
        {
            $context = new RabbitMqContext($incoming, $channel, $this->messageSerializer, $this->logger);

            /** @var ReceivedMessage $message */
            $message = $this->messageSerializer->unserialize($incoming->content);

            $kernel->handleMessage($message->message, $context);
        }
        catch(\Throwable $throwable)
        {
            $this->logger->error(ThrowableFormatter::toString($throwable));
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
            $this->logger->error(ThrowableFormatter::toString($throwable));
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
}
