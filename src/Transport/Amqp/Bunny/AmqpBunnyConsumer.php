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
use Amp\Promise;
use Amp\Success;
use Bunny\Channel;
use Bunny\Message as BunnyMessage;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Transport\Amqp\AmqpQueue;
use Desperado\ServiceBus\Transport\Consumer;
use Desperado\ServiceBus\Transport\IncomingEnvelope;
use Desperado\ServiceBus\Transport\Marshal\Decoder\TransportMessageDecoder;
use Desperado\ServiceBus\Transport\Marshal\Exceptions\DecodeMessageFailed;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 *
 */
final class AmqpBunnyConsumer implements Consumer
{
    public const STOP_MESSAGE_CONTENT = 'quit';

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
     * Reason for end of subscription. If specified, the next cycle will stop the cycle
     *
     * @var string|null
     */
    private $cancelSubscriptionReason;

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
        $this->listenQueue = $listenQueue;
        $this->channel = $channel;
        $this->messageDecoder = $messageDecoder;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @inheritDoc
     */
    public function listen(callable $messageProcessor): void
    {
        $this->consumerTag = \sha1(uuid());
        $logger = $this->logger;

        $this->channel->run(
            function(BunnyMessage $envelope, Channel $channel) use ($messageProcessor, $logger): \Generator
            {
                if(self::STOP_MESSAGE_CONTENT !== $envelope->content)
                {
                    yield $this->process($envelope, $channel, $logger, $messageProcessor);
                }
                else
                {
                    yield static::acknowledge($channel, $envelope, $logger);

                    $this->cancelSubscription('Received stop message command');
                }

                yield $this->checkCycleActivity();
            },
            (string) $this->listenQueue, $this->consumerTag
        );
    }

    /**
     * Handle message
     *
     * @param BunnyMessage    $envelope
     * @param Channel         $channel
     * @param LoggerInterface $logger
     * @param callable        $messageProcessor static function (IncomingEnvelope $incomingEnvelope): void {}
     *
     * @return Promise<null>
     */
    private function process(BunnyMessage $envelope, Channel $channel, LoggerInterface $logger, callable $messageProcessor): Promise
    {
        $operationId = uuid();
        $decoder = $this->messageDecoder;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(string $operationId, BunnyMessage $envelope) use ($messageProcessor, $channel, $logger, $decoder): \Generator
            {
                try
                {
                    yield call(
                        $messageProcessor,
                        static::transformEnvelope($operationId, $envelope, $decoder)
                    );

                    yield self::acknowledge($channel, $envelope, $logger);
                }
                catch(DecodeMessageFailed $exception)
                {
                    self::logDecodeFailed($operationId, $envelope, $exception, $logger);

                    yield self::acknowledge($channel, $envelope, $logger);
                }
                catch(\Throwable $throwable)
                {
                    $this->logThrowable($operationId, $envelope, $throwable, $logger);

                    $this->reject($channel, $envelope, $logger, true);
                }
            },
            $operationId, $envelope
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
    private function reject(Channel $channel, BunnyMessage $envelope, LoggerInterface $logger, bool $retry = false): Promise
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
     * @param string                  $operationId
     * @param BunnyMessage            $envelope
     * @param TransportMessageDecoder $decoder
     *
     * @return IncomingEnvelope
     */
    private static function transformEnvelope(
        string $operationId,
        BunnyMessage $envelope,
        TransportMessageDecoder $decoder
    ): IncomingEnvelope
    {
        $body = $envelope->content;
        $unserialized = $decoder->unserialize($body);

        return new IncomingEnvelope(
            $operationId,
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
     * Mark subscription as canceled
     *
     * @param string $reason
     *
     * @return void
     */
    private function cancelSubscription(string $reason): void
    {
        $this->cancelSubscriptionReason = $reason;
    }

    /**
     * If a command was issued to stop the loop, perform this
     *
     * @return Promise<null>
     */
    private function checkCycleActivity(): Promise
    {
        if(null === $this->cancelSubscriptionReason)
        {
            return new Success();
        }

        $channel = $this->channel;
        $logger = $this->logger;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(string $consumerTag, string $reason) use ($channel, $logger): \Generator
            {
                $logger->info(
                    'Cancel subscription with reason: "{cancelSubscriptionReason}"', [
                        'cancelSubscriptionReason' => $reason,
                        'consumerTag'              => $consumerTag
                    ]
                );

                try
                {
                    yield $channel->cancel($consumerTag, false);
                }
                catch(\Throwable $throwable)
                {
                    /** Not interested */
                }
            },
            $this->consumerTag, $this->cancelSubscriptionReason
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
