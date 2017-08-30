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

    /**
     * Commands exchange
     *
     * @var string
     */
    private $commandExchangeName;

    /**
     * Events exchange
     *
     * @var string
     */
    private $eventExchangeName;

    /**
     * Messages queue
     *
     * @var string
     */
    private $messagesQueue;

    /**
     * @param string                     $connectionDSN
     * @param string                     $entryPoint
     * @param LoggerInterface            $logger
     * @param MessageSerializerInterface $messageSerializer
     * @param array                      $exchanges
     */
    public function __construct(
        string $connectionDSN,
        string $entryPoint,
        LoggerInterface $logger,
        MessageSerializerInterface $messageSerializer,
        array $exchanges
    )
    {
        $this->connectionDsnParts = \parse_url($connectionDSN);
        $this->entryPoint = $entryPoint;
        $this->messageSerializer = $messageSerializer;
        $this->logger = $logger;
        $this->eventLoop = EventLoop::getLoop();

        $this->setupExchangesName($exchanges);
        $this->setupQueueName();

        $this->subscriber = new Async\Client($this->eventLoop, $this->connectionDsnParts);
    }

    /**
     * @inheritdoc
     */
    public function run(KernelInterface $kernel): void
    {
        $this->initSignals();


        $this->subscriber
            ->connect()
            ->then(
                function(Async\Client $client)
                {
                    return $client->channel();
                }
            )
            ->then(function(Channel $channel)
            {
                return $channel
                    ->qos(0, 5)
                    ->then(
                        function() use ($channel)
                        {
                            return $channel;
                        }
                    );
            }
            )
            ->then(
                function(Channel $channel) use ($kernel)
                {
                    $this->logger->debug(
                        \sprintf(
                            'Connected to rabbit mq queue "%s:%s"',
                            $this->connectionDsnParts['host'], $this->connectionDsnParts['port']
                        )
                    );

                    return $channel
                        ->exchangeDeclare($this->commandExchangeName)
                        ->then(
                            function() use ($channel, $kernel)
                            {
                                return $channel->exchangeDeclare($this->eventExchangeName);
                            },
                            function()
                            {
                                $this->logger->error(
                                    \sprintf('Execute declare "%s" exchange failed', $this->commandExchangeName)
                                );
                            }
                        )
                        ->then(
                            function() use ($channel, $kernel)
                            {
                                return $channel->queueDeclare($this->messagesQueue, false, true);
                            },
                            function()
                            {
                                $this->logger->error(
                                    \sprintf('Execute declare "%s" exchange failed', $this->commandExchangeName)
                                );
                            }
                        )
                        ->then(
                            function(MethodQueueDeclareOkFrame $frame) use ($channel, $kernel)
                            {
                                $this->logger->debug('Exchanges configuration successful finished. Start subscribe');

                                return $channel
                                    ->consume(
                                        function(Message $incoming, Channel $channel) use ($kernel)
                                        {
                                            $this->eventLoop
                                                ->futureTick(
                                                    function() use ($incoming, $channel, $kernel)
                                                    {
                                                        $this->handleMessage($kernel, $incoming, $channel);
                                                    }
                                                );
                                        },
                                        $frame->queue
                                    );
                            },
                            function()
                            {
                                $this->logger->error(
                                    \sprintf('Execute declare "%s" queue failed', $this->messagesQueue)
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
     * Setup exchanges name
     *
     * @param array $exchanges
     *
     * @return void
     */
    private function setupExchangesName(array $exchanges): void
    {
        if(1 === \count($exchanges))
        {
            $baseName = \end($exchanges);
            \reset($exchanges);

            $this->commandExchangeName = $baseName;
            $this->eventExchangeName = \sprintf('%s.events', $baseName);
        }
        else
        {
            [$this->commandExchangeName, $this->eventExchangeName] = $exchanges;
        }
    }

    /**
     * Setup messages queue name
     *
     * @return void
     */
    private function setupQueueName(): void
    {
        $this->messagesQueue = \sprintf('%s.messages', $this->entryPoint);
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
