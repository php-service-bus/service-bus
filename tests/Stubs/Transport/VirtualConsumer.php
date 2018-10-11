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

use function Amp\asyncCall;
use Amp\Promise;
use Amp\Success;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageDecoder;
use Desperado\ServiceBus\Transport\Consumer;
use Desperado\ServiceBus\Transport\IncomingEnvelope;
use Desperado\ServiceBus\Transport\TransportContext;

/**
 *
 */
final class VirtualConsumer implements Consumer
{
    /**
     * Restore the message object from string
     *
     * @var MessageDecoder
     */
    private $messageDecoder;

    /**
     * @param MessageDecoder $messageDecoder
     */
    public function __construct(MessageDecoder $messageDecoder)
    {
        $this->messageDecoder = $messageDecoder;
    }

    /**
     * @inheritDoc
     */
    public function listen(callable $messageProcessor): void
    {
        if(true === VirtualTransportBuffer::instance()->has())
        {
            [$messagePayload, $headers] = VirtualTransportBuffer::instance()->extract();

            $envelope = new IncomingEnvelope(
                $messagePayload,
                $this->messageDecoder->decode($messagePayload),
                $headers
            );

            asyncCall($messageProcessor, $envelope, TransportContext::messageReceived(uuid()));
        }
    }

    /**
     * @inheritDoc
     */
    public function stop(): Promise
    {
        return new Success();
    }
}
