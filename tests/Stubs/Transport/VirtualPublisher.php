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

namespace Desperado\ServiceBus\Tests\Stubs\Transport;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageEncoder;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Transport\OutboundEnvelope;
use Desperado\ServiceBus\Transport\Publisher;

/**
 *
 */
final class VirtualPublisher implements Publisher
{
    /**
     * @var MessageEncoder
     */
    private $messageEncoder;

    /**
     * @param MessageEncoder $messageEncoder
     */
    public function __construct(MessageEncoder $messageEncoder)
    {
        $this->messageEncoder = $messageEncoder;
    }

    /**
     * @inheritDoc
     */
    public function createEnvelope(Message $message, array $headers = []): OutboundEnvelope
    {
        return new VirtualOutboundEnvelope(
            $this->messageEncoder->encode($message),
            $headers
        );
    }

    /**
     * @inheritDoc
     */
    public function send(Destination $destination, OutboundEnvelope $envelope): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            static function(OutboundEnvelope $envelope): void
            {
                if(false !== \strpos($envelope->messageContent(), 'FailedMessageSendMarkerEvent'))
                {
                    /** @see ServiceBusKernelTest::failedResponseDelivery */
                    throw new \LogicException('shit happens');
                }

                VirtualTransportBuffer::instance()->add($envelope->messageContent(), $envelope->headers());
            },
            $envelope
        );
    }
}
