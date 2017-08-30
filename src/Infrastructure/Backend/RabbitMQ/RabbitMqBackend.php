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
use Psr\Log\LoggerInterface;
use EventLoop\EventLoop;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Desperado\ConcurrencyFramework\Common\Formatter\ThrowableFormatter;
use Desperado\ConcurrencyFramework\Domain\Messages\ReceivedMessage;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Application\KernelInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Backend\BackendInterface;

/**
 * ReactPHP rabbit mq client
 */
class RabbitMqBackend implements BackendInterface
{

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
    private $subscriber;

    private $retry;

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
        $this->subscriber = new Async\Client($this->eventLoop, $this->connectionDsnParts, $logger);
        $this->initSignals();
    }

    /**
     * @inheritdoc
     */
    public function run(KernelInterface $kernel): void
    {
        $this->subscriber
            ->connect()
            ->then(
                function(Async\Client $client)
                {
                    return $client
                        ->channel()
                        ->then(
                            function(Channel $channel)
                            {
                                return $channel
                                    ->qos(0, 1)
                                    ->then(
                                        function() use ($channel)
                                        {
                                            return $channel;
                                        }
                                    );
                            }
                        )
                        ->then(
                            function(Channel $channel)
                            {
                                return $channel
                                    ->exchangeDeclare($this->entryPoint)
                                    ->then(
                                        function() use ($channel)
                                        {
                                            return $channel
                                                ->queueDeclare($this->entryPoint);
                                        }
                                    )
                                    ->then(
                                        function(MethodQueueDeclareOkFrame $frame) use ($channel)
                                        {

                                            $channel
                                                ->consume(
                                                    function(Message $incoming, Channel $channel)
                                                    {
                                                        $this->eventLoop
                                                            ->futureTick(
                                                                function() use ($incoming, $channel)
                                                                {
                                                                    echo PHP_EOL . $incoming->content . PHP_EOL;

                                                                    $channel->ack($incoming);
                                                                }
                                                            );
                                                    },
                                                    $frame->queue
                                                );
                                        }
                                    );
                            }
                        );
                }
            );


        $this->eventLoop->run();
    }

    /**
     * @inheritdoc
     */
    public function stop(): void
    {
        if(null !== $this->subscriber)
        {
            $callable = function()
            {
                $this->subscriber->stop();
            };

            $this->subscriber
                ->disconnect()
                ->then($callable, $callable);
        }

        $this->logger->debug('RabbitMQ queue daemon stopped');
        exit(0);
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
        die('3');
        try
        {
            $context = new RabbitMqContext(
                $this->eventLoop,
                $this->connectionDsnParts,
                $this->messageSerializer
            );

            /** @var ReceivedMessage $message */
            $message = $this->messageSerializer->unserialize($incoming->content);

            $kernel->handleMessage($message->getMessage(), $context);
        }
        catch(\Throwable $throwable)
        {
            $this->logger->error(ThrowableFormatter::toString($throwable));
        }

        $channel->ack($incoming);
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
     * @param string   $exchangeMain
     * @param callable $consume
     *
     * @return callable
     */
    private function getRetryCallback(string $exchangeMain, callable $consume): callable
    {
        static $interval = 0.5;

        return function(\Throwable $error) use (&$interval, $exchangeMain, $consume)
        {
            $this->logger->critical(ThrowableFormatter::toString($error));

            $this->logger->info(\sprintf('Try to reconnect after %s seconds.', $interval));

            $this->eventLoop->addTimer($interval, function() use ($exchangeMain, $consume)
            {
                $this->connectToBroker($exchangeMain, $consume);
            });

            $interval = 60 > $interval ? $interval * 2 : 0.5;
        };
    }
}
