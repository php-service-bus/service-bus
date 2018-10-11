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
use Bunny\Channel;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageEncoder;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Transport\Amqp\AmqpOutboundEnvelope;
use Desperado\ServiceBus\Transport\Exceptions\MessageSendFailed;
use Desperado\ServiceBus\Transport\OutboundEnvelope;
use Desperado\ServiceBus\Transport\Publisher;

/**
 *
 */
final class BunnyPublisher implements Publisher
{
    /**
     * @var Channel
     */
    private $channel;

    /**
     * Message encoder
     *
     * @var MessageEncoder
     */
    private $encoder;

    /**
     * @param Channel                 $channel
     * @param MessageEncoder $encoder
     */
    public function __construct(Channel $channel, MessageEncoder $encoder)
    {
        $this->channel = $channel;
        $this->encoder = $encoder;
    }

    /**
     * @inheritDoc
     */
    public function createEnvelope(Message $message, array $headers = []): OutboundEnvelope
    {
        return new AmqpOutboundEnvelope($this->encoder->encode($message), $headers);
    }

    /**
     * @inheritDoc
     */
    public function send(Destination $destination, OutboundEnvelope $envelope): Promise
    {
        $channel = $this->channel;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Destination $destination, OutboundEnvelope $envelope) use ($channel): \Generator
            {
                try
                {
                    yield $channel->publish(
                        $envelope->messageContent(),
                        self::collectHeaders($envelope),
                        $destination->topicName(),
                        $destination->routingKey(),
                        $envelope->isMandatory(),
                        $envelope->isImmediate()
                    );
                }
                catch(\Throwable $throwable)
                {
                    throw new MessageSendFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
            },
            $destination, $envelope
        );
    }

    /**
     * @param OutboundEnvelope $envelope
     *
     * @return array<string, mixed>
     */
    private static function collectHeaders(OutboundEnvelope $envelope): array
    {
        $headers = \array_merge($envelope->headers(), [
            'content-type'     => $envelope->contentType(),
            'content-encoding' => $envelope->contentEncoding(),
            'delivery-mode'    => true === $envelope->isPersistent() ? AmqpOutboundEnvelope::AMQP_DURABLE : null,
            'priority'         => $envelope->priority(),
            'expiration'       => $envelope->expirationTime(),
            'message-id'       => $envelope->messageId(),
            'user-id'          => $envelope->clientId(),
            'app-id'           => $envelope->appId()
        ]);

        return \array_filter($headers);
    }
}
