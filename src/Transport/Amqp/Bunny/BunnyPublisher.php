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
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Transport\Amqp\AmqpOutboundEnvelope;
use Desperado\ServiceBus\Transport\Exceptions\MessageSendFailed;
use Desperado\ServiceBus\Transport\Marshal\Encoder\TransportMessageEncoder;
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
     * @var TransportMessageEncoder
     */
    private $encoder;

    /**
     * @param Channel                 $channel
     * @param TransportMessageEncoder $encoder
     */
    public function __construct(Channel $channel, TransportMessageEncoder $encoder)
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
                        $envelope->headers(),
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
}
