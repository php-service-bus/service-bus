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
use Desperado\ServiceBus\Transport\Consumer;
use Desperado\ServiceBus\Transport\IncomingEnvelope;
use Desperado\ServiceBus\Transport\Marshal\Decoder\TransportMessageDecoder;
use Desperado\ServiceBus\Transport\TransportContext;

/**
 *
 */
final class VirtualConsumer implements Consumer
{
    /**
     * Restore the message object from string
     *
     * @var TransportMessageDecoder
     */
    private $messageDecoder;

    /**
     * @param TransportMessageDecoder $messageDecoder
     */
    public function __construct(TransportMessageDecoder $messageDecoder)
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

            $unserialized = $this->messageDecoder->unserialize($messagePayload);

            $envelope = new IncomingEnvelope(
                $messagePayload,
                $unserialized['message'],
                $this->messageDecoder->denormalize(
                    $unserialized['namespace'],
                    $unserialized['message']
                ),
                $headers
            );

            asyncCall($messageProcessor, $envelope, TransportContext::messageReceived());
        }
    }
}
