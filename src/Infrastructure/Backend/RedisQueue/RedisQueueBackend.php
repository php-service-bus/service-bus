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

namespace Desperado\ConcurrencyFramework\Infrastructure\Backend\RedisQueue;

use function Amp\Iterator\merge;
use Amp\Loop;
use Amp\Redis;
use Desperado\ConcurrencyFramework\Common\Formatter\ThrowableFormatter;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Application\KernelInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Backend\BackendInterface;
use Psr\Log\LoggerInterface;

/**
 * Redis queue backend
 */
class RedisQueueBackend implements BackendInterface
{
    /**
     * Redis subscriber client
     *
     * @var Redis\SubscribeClient
     */
    private $subscriber;

    /**
     * Publisher
     *
     * @var Redis\Client
     */
    private $publisher;

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
     * @param string                     $connectionDSN
     * @param string                     $entryPoint
     * @param MessageSerializerInterface $messageSerializer
     * @param LoggerInterface            $logger
     */
    public function __construct(
        string $connectionDSN,
        string $entryPoint,
        MessageSerializerInterface $messageSerializer,
        LoggerInterface $logger
    )
    {
        $this->subscriber = new Redis\SubscribeClient($connectionDSN);
        $this->publisher = new Redis\Client($connectionDSN);
        $this->entryPoint = $entryPoint;
        $this->messageSerializer = $messageSerializer;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function run(KernelInterface $kernel): void
    {
        $this->initSignals();

        $channels = [$this->entryPoint];

        Loop::run(
            function() use ($kernel, $channels)
            {
                /** Register listeners for each application exchange */
                try
                {
                    $listeners = [];

                    foreach($channels as $channel)
                    {
                        $listeners[] = yield  $this->subscriber->subscribe($channel);

                        $this->logger
                            ->debug(
                                \sprintf(
                                    'Signed to channel "%s" (For "%s" entry point)',
                                    $channel, $this->entryPoint
                                )
                            );
                    }

                    $iterator = merge($listeners);

                    $this->logger->debug(
                        \sprintf('Redis queue daemon for entry point "%s" started', $this->entryPoint)
                    );

                    while(yield $iterator->advance())
                    {
                        $this->handleMessage((string) $iterator->getCurrent(), $kernel);
                    }
                }
                catch(\Throwable $throwable)
                {
                    $this->handleFailedSubscribe($throwable);
                }
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function stop(): void
    {
        $this->subscriber->close();
        $this->logger->debug('Redis queue daemon stopped');

        exit(0);
    }

    /**
     * Handle received task
     *
     * @param string          $serializedMessage
     * @param KernelInterface $kernel
     *
     * @return void
     */
    private function handleMessage(string $serializedMessage, KernelInterface $kernel)
    {
        try
        {
            if('' === $serializedMessage)
            {
                throw new \InvalidArgumentException(
                    'Message payload can\'t be empty'
                );
            }

            $receivedMessage = $this->messageSerializer->unserialize($serializedMessage);

            $kernel->handleMessage(
                $receivedMessage->getMessage(),
                new AmPhpRedisContext($this->publisher, $this->messageSerializer)
            );
        }
        catch(\Throwable $throwable)
        {
            $this->logger->critical(ThrowableFormatter::toString($throwable));
        }
    }

    /**
     * Failed subscribe handle
     *
     * @param \Throwable $throwable
     *
     * @return void
     */
    private function handleFailedSubscribe(\Throwable $throwable): void
    {
        $this->logger
            ->critical(
                \sprintf(
                    'Subscription start failed with error "%s"',
                    ThrowableFormatter::toString($throwable)
                )
            );

        $this->stop();
    }

    /**
     * Init unix signals
     *
     * @return void
     */
    private function initSignals(): void
    {
        Loop::onSignal(
            SIGINT,
            [$this, 'stop']
        );
    }
}
