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

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\Transport\OutboundEnvelope;
use Desperado\ServiceBus\Transport\Publisher;

/**
 *
 */
final class VirtualPublisher implements Publisher
{
    /**
     * @inheritDoc
     */
    public function createEnvelope(Message $message, array $headers = []): OutboundEnvelope
    {

    }

    /**
     * @inheritDoc
     */
    public function send(Destination $destination, OutboundEnvelope $envelope): void
    {

    }
}
