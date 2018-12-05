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
use Desperado\ServiceBus\Infrastructure\Transport\Transport;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Bunny\Message as BunnyEnvelope;

/**
 *
 */
final class BunnyConsumer
{
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
     * @param callable(BunnyIncomingPackage):\Generator $onMessageReceived
     *
     * @return Promise<\Bunny\Protocol\MethodBasicConsumeOkFrame>
     */
    public function listen(callable $onMessageReceived): Promise
    {
        $queueName = (string) $this->queue;
        $logger    = $this->logger;

        $logger->info('Creates new consumer on channel for queue "{queue}" with tag "{consumerTag}"', [
            'queue'       => $queueName,
            'consumerTag' => $this->tag
        ]);

        return $this->channel->consume(
            self::createMessageHandler($logger, $onMessageReceived), $queueName, (string) $this->tag
        );
    }

    /**
     * Stop watching the queue
     *
     * @return Promise It does not return any result
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
     * @param callable(BunnyIncomingPackage):\Generator $onMessageReceived
     *
     * @return callable(BunnyEnvelope, BunnyChannelOverride):void
     */
    private static function createMessageHandler(LoggerInterface $logger, $onMessageReceived): callable
    {
        return static function(BunnyEnvelope $envelope, BunnyChannelOverride $channel) use ($logger, $onMessageReceived): void
        {
            /** Trace ID */
            if(false === isset($envelope->headers[Transport::SERVICE_BUS_TRACE_HEADER]))
            {
                $envelope->headers[Transport::SERVICE_BUS_TRACE_HEADER] = uuid();
            }

            try
            {
                $package = BunnyIncomingPackage::received($channel, $envelope);

                $logger->debug('New message received', [
                    'packageId'         => $package->id(),
                    'traceId'           => $package->traceId(),
                    'rawMessagePayload' => $envelope->content,
                    'rawMessageHeaders' => $envelope->headers
                ]);

                /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
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
        };
    }
}
