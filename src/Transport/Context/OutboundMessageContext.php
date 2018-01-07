<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\Context;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\ServiceBus\Transport\Message\Message;
use Desperado\ServiceBus\Transport\Message\MessageDeliveryOptions;

/**
 * Outbound message context
 */
class OutboundMessageContext
{
    /**
     * The context of the incoming message
     *
     * @var IncomingMessageContextInterface
     */
    private $incomingMessageContext;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * Messages to be sent to the transport bus
     *
     * @var \SplObjectStorage
     */
    private $toPublishMessages;

    /**
     * @param IncomingMessageContextInterface $incomingMessageContext
     * @param MessageSerializerInterface      $messageSerializer
     *
     * @return OutboundMessageContext
     */
    public static function fromIncoming(
        IncomingMessageContextInterface $incomingMessageContext,
        MessageSerializerInterface $messageSerializer
    ): self
    {
        $self = new self();

        $self->incomingMessageContext = $incomingMessageContext;
        $self->messageSerializer = $messageSerializer;
        $self->toPublishMessages = new \SplObjectStorage();

        return $self;
    }

    /**
     * Publish event
     *
     * @param AbstractEvent          $event
     * @param MessageDeliveryOptions $messageDeliveryOptions
     *
     * @return void
     */
    public function publish(AbstractEvent $event, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->addToQueue($event, $messageDeliveryOptions);
    }

    /**
     * Send command
     *
     * @param AbstractCommand        $command
     * @param MessageDeliveryOptions $messageDeliveryOptions
     *
     * @return void
     */
    public function send(AbstractCommand $command, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->addToQueue($command, $messageDeliveryOptions);
    }

    /**
     * Get messages to be sent to the transport bus
     *
     * @return \SplObjectStorage
     */
    public function getToPublishMessages(): \SplObjectStorage
    {
        return $this->toPublishMessages;
    }

    /**
     * Add message to queue
     *
     * @param AbstractMessage        $message
     * @param MessageDeliveryOptions $messageDeliveryOptions
     *
     * @return void
     */
    protected function addToQueue(AbstractMessage $message, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $destination = true === $messageDeliveryOptions->destinationSpecified()
            ? $messageDeliveryOptions->getDestination()
            : $this->incomingMessageContext->getReceivedMessage()->getExchange();

        $routingKey = true === $messageDeliveryOptions->routingKeySpecified()
            ? $messageDeliveryOptions->getRoutingKey()
            : $this->incomingMessageContext->getReceivedMessage()->getRoutingKey();

        $this->toPublishMessages->attach(
            Message::create(
                $this->messageSerializer->serialize($message),
                $messageDeliveryOptions->getHeaders(),
                $destination,
                $routingKey
            )
        );
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
