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
use Desperado\Domain\EntryPoint\DaemonInterface;
use Desperado\Domain\EntryPoint\EntryPointInterface;
use Desperado\Domain\Environment\Environment;
use Desperado\Framework\Application\ApplicationLogger;
use EventLoop\EventLoop;
use Psr\Log\LogLevel;

/**
 * RabbitMQ subscriber
 */
class RabbitMQDaemon implements DaemonInterface
{
    protected const MAX_TASK_IN_PROGRESS = 5;
    protected const LOG_CHANNEL_NAME = 'rabbitMQ';

    /** Exchange types */
    protected const EXCHANGE_TYPE_DIRECT = 'direct';
    protected const EXCHANGE_TYPE_FANOUT = 'fanout';
    protected const EXCHANGE_TYPE_TOPIC = 'topic';

    protected const QOS_DEFAULT_PRE_FETCH_SIZE = 0;
    protected const QOS_DEFAULT_PRE_FETCH_COUNT = 5;

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
     * In progress task registry
     *
     * @var array
     */
    private $tasksInProgress = [];

    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Broker options
     *
     * [
     *    'pre_fetch_size' => 0,
     *    'pre_fetch_count' => 5
     * ]
     *
     * @var array
     */
    private $brokerOptions;

    /**
     * Options example:
     *
     * [
     *    'pre_fetch_size' => 0,
     *    'pre_fetch_count' => 5
     * ]
     *
     * @param string      $connectionDSN
     * @param Environment $environment
     * @param array       $brokerOptions
     */
    public function __construct(string $connectionDSN, Environment $environment, array $brokerOptions = [])
    {
        $this->initSignals();
        $this->initFailHandler();

        $this->environment = $environment;
        $this->client = new Client(
            EventLoop::getLoop(),
            \parse_url($connectionDSN)
        );

        $this->brokerOptions = [
            'pre_fetch_size'  => $brokerOptions['pre_fetch_size'] ?? self::QOS_DEFAULT_PRE_FETCH_SIZE,
            'pre_fetch_count' => $brokerOptions['pre_fetch_count'] ?? self::QOS_DEFAULT_PRE_FETCH_COUNT
        ];
    }

    /**
     * @inheritdoc
     */
    public function run(EntryPointInterface $entryPoint, array $clients = []): void
    {
        ApplicationLogger::info(
            self::LOG_CHANNEL_NAME,
            \sprintf('"%s" created', \get_class(EventLoop::getLoop()))
        );

        $this->connect(
            function(Message $incoming, Channel $channel) use ($entryPoint)
            {
                EventLoop::getLoop()->futureTick(
                    function() use ($incoming, $channel, $entryPoint)
                    {
                        $this->handleMessage($entryPoint, $incoming, $channel);
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
        $incomeMessageHash = \hash('sha512', $incoming->content);

        if(self::MAX_TASK_IN_PROGRESS > \count($this->tasksInProgress))
        {
            $this->tasksInProgress[$incomeMessageHash] = 1;

            try
            {
                $this->logIncomeMessage($incoming);

                $serializer = $entryPoint->getMessageSerializer();

                $context = new RabbitMqDaemonContext(
                    $incoming,
                    $channel,
                    $serializer,
                    $this->environment
                );

                $entryPoint->handleMessage(
                    $serializer->unserialize($incoming->content),
                    $context
                );

                $channel->ack($incoming);
            }
            catch(\Throwable $throwable)
            {
                ApplicationLogger::throwable(self::LOG_CHANNEL_NAME, $throwable);

                $throwable instanceof \LogicException
                    ? $channel->nack($incoming)
                    : $channel->ack($incoming);
            }

            unset($this->tasksInProgress[$incomeMessageHash]);
        }
    }

    /**
     * Push income message to log
     *
     * @param Message $incoming
     *
     * @return void
     */
    private function logIncomeMessage(Message $incoming): void
    {
        if(true === $this->environment->isDebug())
        {
            ApplicationLogger::debug(
                self::LOG_CHANNEL_NAME,
                \sprintf(
                    'Message received: "%s" with headers "%s"',
                    $incoming->content,
                    \urldecode(\http_build_query((array) $incoming->headers))
                )
            );
        }
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
                            function(Channel $channel)
                            {
                                return $channel
                                    ->qos($this->brokerOptions['pre_fetch_size'], $this->brokerOptions['pre_fetch_count'])
                                    ->then(
                                        function() use ($channel)
                                        {
                                            return $channel;
                                        },
                                        $this->failPromiseResultHandler
                                    );
                            },
                            $this->failPromiseResultHandler
                        )
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
}
