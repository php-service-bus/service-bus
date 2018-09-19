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

namespace Desperado\ServiceBus\Transport;

use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\OutboundMessage\Destination;

/**
 *
 */
interface Publisher
{
    /**
     * Create message
     *
     * @param Message $message
     * @param array   $headers
     *
     * @return OutboundEnvelope
     *
     * @throws \Desperado\ServiceBus\Transport\Marshal\Exceptions\EncodeMessageFailed
     */
    public function createEnvelope(Message $message, array $headers = []): OutboundEnvelope;

    /**
     * Send message to broker
     *
     * @param Destination      $destination
     * @param OutboundEnvelope $envelope
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\NotConfiguredTopic
     * @throws \Desperado\ServiceBus\Transport\Exceptions\MessageSendFailed
     */
    public function send(Destination $destination, OutboundEnvelope $envelope): Promise;
}
