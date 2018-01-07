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

use Desperado\ServiceBus\Transport\Context\OutboundMessageContext;
use Desperado\ServiceBus\Transport\Message\Message;
use Desperado\ServiceBus\Transport\Context\IncomingMessageContextInterface;

/**
 * Inbound message packet
 */
class IncomingMessageContainer
{
    /**
     * Incoming message
     *
     * @var Message
     */
    private $message;

    /**
     * Incoming context
     *
     * @var IncomingMessageContextInterface
     */
    private $incomingMessageContext;

    /**
     * Outbound message context
     *
     * @var OutboundMessageContext
     */
    private $outboundContext;

    /**
     * @param Message                         $message
     * @param IncomingMessageContextInterface $incomingMessageContext
     * @param OutboundMessageContext          $outboundContext
     *
     * @return IncomingMessageContainer
     */
    public static function new(
        Message $message,
        IncomingMessageContextInterface $incomingMessageContext,
        OutboundMessageContext $outboundContext
    ): self
    {
        $self = new self();

        $self->message = $message;
        $self->incomingMessageContext = $incomingMessageContext;
        $self->outboundContext = $outboundContext;

        return $self;
    }

    /**
     * Get received message
     *
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * Get incoming context
     *
     * @return IncomingMessageContextInterface
     */
    public function getIncomingMessageContext(): IncomingMessageContextInterface
    {
        return $this->incomingMessageContext;
    }

    /**
     * Get outbound context
     *
     * @return OutboundMessageContext
     */
    public function getOutboundContext(): OutboundMessageContext
    {
        return $this->outboundContext;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
