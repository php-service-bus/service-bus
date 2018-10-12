<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Infrastructure\Transport\Implementation\BunnyRabbitMQ;

use function Amp\asyncCall;
use function Amp\call;
use Amp\Promise;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Bunny\Message as BunnyEnvelope;

/**
 *
 */
final class BunnyConsumer
{
    /** Maximum number of messages to be executed simultaneously */
    private const MAX_PROCESSED_MESSAGES_COUNT = 50;

    /**
     * @var BunnyChannelOverride
     */
    private $channel;

    /**
     * Listen queue
     *
     * @var AmqpQueue
     */
    private $queue;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Consumer tag
     *
     * @var string|null
     */
    private $tag;

    /**
     * Current in progress messages
     *
     * @var array<string, bool>
     */
    private static $messagesInProcess = [];

    /**
     * @param AmqpQueue            $queue
     * @param BunnyChannelOverride $channel
     * @param LoggerInterface|null $logger
     */
    public function __construct(AmqpQueue $queue, BunnyChannelOverride $channel, ?LoggerInterface $logger = null)
    {
        $this->tag = uuid();

        $this->queue   = $queue;
        $this->channel = $channel;
        $this->logger  = $logger ?? new NullLogger();
    }

    /**
     * Listen queue messages
     *
     * @param callable $onMessageReceived function(BunnyIncomingPackage $package) {...}
     *
     * @return Promise<null>
     */
    public function listen(callable $onMessageReceived): Promise
    {
        $queueName = (string) $this->queue;
        $logger    = $this->logger;

        $logger->info('Creates new consumer on channel for queue "{queue}" with tag "{consumerTag}"', [
            'queue' => $queueName,
            'tag'   => $this->tag
        ]);

        return $this->channel->consume(
            self::createMessageHandler($logger, $onMessageReceived), $queueName, $this->tag
        );
    }

    /**
     * Stop watching the queue
     *
     * @return Promise<null>
     */
    public function stop(): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(): \Generator
            {
                yield $this->channel->cancel($this->tag, false);

                $this->logger->info('Subscription canceled', [
                        'queue'       => (string) $this->queue,
                        'consumerTag' => $this->tag
                    ]
                );

            }
        );
    }

    /**
     * Create listener callback
     *
     * @param LoggerInterface $logger
     * @param callable        $onMessageReceived function(BunnyIncomingPackage $package) {...}
     *
     * @return callable function(BunnyEnvelope $envelope, BunnyChannelOverride $channel) {...}
     */
    private static function createMessageHandler(LoggerInterface $logger, $onMessageReceived): callable
    {
        return static function(BunnyEnvelope $envelope, BunnyChannelOverride $channel) use ($logger, $onMessageReceived): void
        {
            $id = uuid();

            $inProgressCount = \count(self::$messagesInProcess);

            if(self::MAX_PROCESSED_MESSAGES_COUNT >= $inProgressCount)
            {
                self::$messagesInProcess[$id] = true;

                try
                {
                    $package = BunnyIncomingPackage::received($channel, $envelope);

                    asyncCall($onMessageReceived, $package);

                    unset($package);
                }
                catch(\Throwable $throwable)
                {
                    $logger->error('Error occurred: {throwableMessage}', [
                            'throwableMessage'  => $throwable->getMessage(),
                            'throwablePoint'    => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine()),
                            'rawMessagePayload' => $envelope->content
                        ]
                    );
                }
                finally
                {
                    unset(self::$messagesInProcess[$id]);
                }
            }
        };
    }
}
