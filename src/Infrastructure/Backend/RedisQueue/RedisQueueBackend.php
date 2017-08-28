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
use Desperado\ConcurrencyFramework\Infrastructure\Application\ApplicationInterface;
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
     * Channels to subscribe
     *
     * @var array
     */
    private $channels;

    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPoint;

    /**
     * @param string                     $connectionDSN
     * @param string                     $entryPoint
     * @param array                      $channels
     * @param MessageSerializerInterface $messageSerializer
     * @param LoggerInterface            $logger
     */
    public function __construct(
        string $connectionDSN,
        string $entryPoint,
        array $channels = [],
        MessageSerializerInterface $messageSerializer,
        LoggerInterface $logger
    )
    {
        $this->subscriber = new Redis\SubscribeClient($connectionDSN);
        $this->publisher = new Redis\Client($connectionDSN);
        $this->entryPoint = $entryPoint;
        $this->channels = \array_unique($channels);
        $this->messageSerializer = $messageSerializer;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function run(ApplicationInterface $application): void
    {
        $this->initSignals();

        Loop::run(
            function() use ($application)
            {
                /** Register listeners for each application exchange */
                try
                {
                    $listeners = [];

                    foreach($this->channels as $channel)
                    {
                        $listeners[] = yield  $this->subscriber->subscribe($channel);

                        $this->logger
                            ->debug(
                                'Signed to channel "{channel}" (For "{entryPoint}" entry point)',
                                ['channel' => $channel, 'entryPoint' => $this->entryPoint]
                            );
                    }

                    $iterator = merge($listeners);

                    $this->logger->debug(
                        'Redis queue daemon for entry point "{entryPoint}" started',
                        ['entryPoint' => $this->entryPoint]
                    );

                    while(yield $iterator->advance())
                    {
                        $this->handleMessage((string) $iterator->getCurrent(), $application);
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
     * @param string               $serializedMessage
     * @param ApplicationInterface $application
     *
     * @return void
     */
    private function handleMessage(string $serializedMessage, ApplicationInterface $application)
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

            $message = $receivedMessage->getMessage();
            $metadata = $receivedMessage->getMetadata();

            $context = new AmPhpRedisContext($this->publisher, $this->messageSerializer);

            $this->logger
                ->debug(
                    'Received message "{messageType}" with metadata "{metadata}"',
                    [
                        'messageType' => \get_class($message),
                        'metadata'    => \urldecode(\http_build_query($metadata->all()))
                    ]
                );

            $application->handleMessage($message, $context);
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
