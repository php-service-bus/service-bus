<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport;

use Desperado\Contracts\Common\Message;

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
     * @throws \Desperado\ServiceBus\Transport\Exceptions\EncodeMessageFailed
     */
    public function createEnvelope(Message $message, array $headers = []): OutboundEnvelope;

    /**
     * Send message to broker
     *
     * @param Destination      $destination
     * @param OutboundEnvelope $envelope
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\NotConfiguredTopic
     * @throws \Desperado\ServiceBus\Transport\Exceptions\MessageSendFailed
     */
    public function send(Destination $destination, OutboundEnvelope $envelope): void;
}
