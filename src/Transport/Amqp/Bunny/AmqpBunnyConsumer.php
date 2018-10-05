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

namespace Desperado\ServiceBus\Transport\Amqp\Bunny;

use function Amp\call;
use Amp\Loop;
use Amp\Promise;
use Bunny\Channel;
use Bunny\Message as BunnyMessage;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Transport\Amqp\AmqpQueue;
use Desperado\ServiceBus\Transport\Consumer;
use Desperado\ServiceBus\Transport\IncomingEnvelope;
use Desperado\ServiceBus\Transport\Marshal\Decoder\TransportMessageDecoder;
use Desperado\ServiceBus\Transport\Marshal\Exceptions\DecodeMessageFailed;
use Desperado\ServiceBus\Transport\TransportContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 *
 */
final class AmqpBunnyConsumer implements Consumer
{
    /** Maximum number of messages to be executed simultaneously */
    private const MAX_PROCESSED_MESSAGES_COUNT = 50;


    /**
     * @var AmqpQueue
     */
    private $listenQueue;

    /**
     * @var Channel
     */
    private $channel;

    /**
     * Restore the message object from string
     *
     * @var TransportMessageDecoder
     */
    private $messageDecoder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * A string describing this consumer. Used for canceling subscriptions with cancel()
     *
     * @var string
     */
    private $consumerTag;

    /**
     * Current in progress messages
     *
     * @var array<string, bool>
     */
    private static $messagesInProcess = [];

    /**
     * @param AmqpQueue               $listenQueue
     * @param Channel                 $channel
     * @param TransportMessageDecoder $messageDecoder
     * @param LoggerInterface|null    $logger
     */
    public function __construct(
        AmqpQueue $listenQueue,
        Channel $channel,
        TransportMessageDecoder $messageDecoder,
        LoggerInterface $logger = null
    )
    {
        $this->listenQueue    = $listenQueue;
        $this->channel        = $channel;
        $this->messageDecoder = $messageDecoder;
        $this->logger         = $logger ?? new NullLogger();
    }

    /**
     * @inheritDoc
     */
    public function listen(callable $messageProcessor): void
    {
        $consumerTag = $this->consumerTag = \sha1(uuid());

        $logger  = $this->logger;
        $decoder = $this->messageDecoder;
        $channel = $this->channel;

        $channel->consume(
            static function(BunnyMessage $envelope, Channel $channel) use ($consumerTag, $messageProcessor, $decoder, $logger): \Generator
            {
                $context     = TransportContext::messageReceived($consumerTag);
                $operationId = $context->id();

                $inProgressCount = \count(self::$messagesInProcess);

                if(self::MAX_PROCESSED_MESSAGES_COUNT >= $inProgressCount)
                {
                    static::$messagesInProcess[$operationId] = true;

                    try
                    {
                        $transformedEnvelope = self::transformEnvelope($envelope, $decoder);

                        yield call($messageProcessor, $transformedEnvelope, $context);

                        unset($transformedEnvelope);

                        yield self::acknowledge($channel, $envelope, $logger);
                    }
                    catch(DecodeMessageFailed $exception)
                    {
                        self::logDecodeFailed($operationId, $envelope, $exception, $logger);

                        yield self::acknowledge($channel, $envelope, $logger);
                    }
                    catch(\Throwable $throwable)
                    {
                        self::logThrowable($operationId, $envelope, $throwable, $logger);

                        yield self::reject($channel, $envelope, $logger, true);
                    }

                    unset(self::$messagesInProcess[$operationId]);
                }

                unset($context, $operationId);
            },
            (string) $this->listenQueue, $this->consumerTag
        );
    }

    /**
     * @inheritDoc
     */
    public function stop(): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(): \Generator
            {
                yield $this->channel->cancel($this->consumerTag, false);

                $this->logger->info('Subscription canceled', [
                        'queue'       => (string) $this->listenQueue,
                        'consumerTag' => $this->consumerTag
                    ]
                );

            }
        );
    }

    /**
     * Message accepted for processing
     *
     * @param Channel         $channel
     * @param BunnyMessage    $envelope
     * @param LoggerInterface $logger
     *
     * @return Promise<null>
     */
    private static function acknowledge(Channel $channel, BunnyMessage $envelope, LoggerInterface $logger): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(BunnyMessage $envelope) use ($channel, $logger): \Generator
            {
                try
                {
                    yield $channel->ack($envelope);
                }
                catch(\Throwable $throwable)
                {
                    $logger->error(
                        'Acknowledge error: "{throwableMessage}"', [
                            'throwableMessage' => $throwable->getMessage(),
                            'throwablePoint'   => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                        ]
                    );
                }
            },
            $envelope
        );
    }

    /**
     * The message was not accepted to processing. When specifying a $retry flag, it will be re-sent to the queue
     *
     * @param Channel         $channel
     * @param BunnyMessage    $envelope
     * @param LoggerInterface $logger
     * @param bool            $retry
     *
     * @return Promise<null>
     */
    private static function reject(Channel $channel, BunnyMessage $envelope, LoggerInterface $logger, bool $retry = false): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(BunnyMessage $envelope, bool $retry) use ($channel, $logger): \Generator
            {
                try
                {
                    yield $channel->reject($envelope, $retry);
                }
                catch(\Throwable $throwable)
                {
                    $logger->error(
                        'Error while rejecting message: "{throwableMessage}"', [
                            'throwableMessage' => $throwable->getMessage(),
                            'throwablePoint'   => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())
                        ]
                    );
                }
            },
            $envelope, $retry
        );
    }

    /**
     * Create incoming message envelope
     *
     * @param BunnyMessage            $envelope
     * @param TransportMessageDecoder $decoder
     *
     * @return IncomingEnvelope
     */
    private static function transformEnvelope(BunnyMessage $envelope, TransportMessageDecoder $decoder): IncomingEnvelope
    {
        $body         = $envelope->content;
        $unserialized = $decoder->unserialize($body);

        return new IncomingEnvelope(
            $body,
            $unserialized['message'],
            $decoder->denormalize(
                $unserialized['namespace'],
                $unserialized['message']
            ),
            $envelope->headers
        );
    }

    /**
     * @param string          $operationId
     * @param BunnyMessage    $envelope
     * @param \Throwable      $throwable
     * @param LoggerInterface $logger
     *
     * @return void
     */
    private static function logThrowable(
        string $operationId,
        BunnyMessage $envelope,
        \Throwable $throwable,
        LoggerInterface $logger
    ): void
    {
        $logger->error(
            'Error processing message: "{throwableMessage}"', [
                'throwableMessage'  => $throwable->getMessage(),
                'throwablePoint'    => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine()),
                'operationId'       => $operationId,
                'rawMessagePayload' => $envelope->content,
            ]
        );
    }

    /**
     * @param string              $operationId
     * @param BunnyMessage        $envelope
     * @param DecodeMessageFailed $exception
     * @param LoggerInterface     $logger
     *
     * @return void
     */
    private static function logDecodeFailed(
        string $operationId,
        BunnyMessage $envelope,
        DecodeMessageFailed $exception,
        LoggerInterface $logger
    ): void
    {
        $logger->error(
            'An incorrectly serialized message was received. Error details: "{throwableMessage}"', [
                'throwableMessage'  => $exception->getMessage(),
                'throwablePoint'    => \sprintf('%s:%d', $exception->getFile(), $exception->getLine()),
                'operationId'       => $operationId,
                'rawMessagePayload' => $envelope->content
            ]
        );
    }
}
