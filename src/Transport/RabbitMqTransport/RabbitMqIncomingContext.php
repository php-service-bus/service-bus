<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\RabbitMqTransport;

use Bunny\Channel;
use Desperado\ServiceBus\Transport\Context\IncomingMessageContextInterface;
use Desperado\ServiceBus\Transport\Message\Message;

/**
 * RabbitMQ incoming message context
 */
final class RabbitMqIncomingContext implements IncomingMessageContextInterface
{
    /**
     * Received message
     *
     * @var Message
     */
    private $message;

    /**
     * Message channel
     *
     * @var Channel
     */
    private $channel;

    /**
     * Create received message context
     *
     * @param Message $message
     * @param Channel $channel
     *
     * @return self
     */
    public static function create(Message $message, Channel $channel): self
    {
        $self = new self();

        $self->message = $message;
        $self->channel = $channel;

        return $self;
    }

    /**
     * @inheritdoc
     */
    public function getReceivedMessage(): Message
    {
        return $this->message;
    }

    /**
     * Get channel
     *
     * @return Channel
     */
    public function getChannel(): Channel
    {
        return $this->channel;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
