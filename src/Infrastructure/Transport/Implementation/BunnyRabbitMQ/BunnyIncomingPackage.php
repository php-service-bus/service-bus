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

use function Amp\call;
use Amp\Promise;
use Bunny\Message as BunnyEnvelope;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Endpoint\TransportLevelDestination;
use Desperado\ServiceBus\Infrastructure\Transport\Exceptions\AcknowledgeFailed;
use Desperado\ServiceBus\Infrastructure\Transport\Exceptions\NotAcknowledgeFailed;
use Desperado\ServiceBus\Infrastructure\Transport\Exceptions\RejectFailed;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpTransportLevelDestination;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;

/**
 *
 */
final class BunnyIncomingPackage implements IncomingPackage
{
    /**
     * Received package id
     *
     * @var string
     */
    private $id;

    /**
     * The time the message was received (Unix timestamp with microseconds)
     *
     * @var float
     */
    private $time;

    /**
     * @var BunnyEnvelope
     */
    private $originMessage;

    /**
     * @var BunnyChannelOverride
     */
    private $channel;

    /**
     * @param BunnyChannelOverride $channel
     * @param BunnyEnvelope        $message
     *
     * @return self
     */
    public static function received(BunnyChannelOverride $channel, BunnyEnvelope $message): self
    {
        $self = new self();

        $self->channel = $channel;
        $self->originMessage = $message;

        return $self;
    }

    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function time(): float
    {
        return $this->time;
    }

    /**
     * @inheritDoc
     */
    public function origin(): TransportLevelDestination
    {
        return new AmqpTransportLevelDestination(
            $this->originMessage->exchange,
            $this->originMessage->routingKey
        );
    }

    /**
     * @inheritDoc
     */
    public function payload(): string
    {
        return $this->originMessage->content;
    }

    /**
     * @inheritDoc
     */
    public function headers(): array
    {
        return $this->originMessage->headers;
    }

    /**
     * @inheritDoc
     */
    public function ack(): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(): \Generator
            {
                try
                {
                    yield $this->channel->ack($this->originMessage);
                }
                catch(\Throwable $throwable)
                {
                    throw new AcknowledgeFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function nack(bool $requeue, ?string $withReason = null): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(bool $requeue): \Generator
            {
                try
                {
                    yield $this->channel->nack($this->originMessage->deliveryTag, false, $requeue);
                }
                catch(\Throwable $throwable)
                {
                    throw new NotAcknowledgeFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
            },
            $requeue
        );
    }

    /**
     * @inheritDoc
     */
    public function reject(bool $requeue, ?string $withReason = null): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(bool $requeue): \Generator
            {
                try
                {
                    yield $this->channel->reject($this->originMessage->deliveryTag, $requeue);
                }
                catch(\Throwable $throwable)
                {
                    throw new RejectFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
            },
            $requeue
        );
    }

    /**
     * @inheritdoc
     */
    public function traceId(): string
    {
        /**
         * @see BunnyConsumer::createMessageHandler#144
         *
         * @var string $traceId
         */
        $traceId = (string) $this->originMessage->headers[Transport::SERVICE_BUS_TRACE_HEADER];

        return $traceId;
    }

    private function __construct()
    {
        $this->id = uuid();
        $this->time = (float) \microtime(true);
    }
}
