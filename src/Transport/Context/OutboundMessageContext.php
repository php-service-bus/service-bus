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
use Desperado\Domain\Transport\Context\IncomingMessageContextInterface;
use Desperado\Domain\Transport\Context\OutboundMessageContextInterface;
use Desperado\Domain\Transport\Message\Message;
use Desperado\Domain\Transport\Message\MessageDeliveryOptions;
use Desperado\ServiceBus\HttpServer\Context\HttpIncomingContext;
use Psr\Http\Message\RequestInterface;

/**
 * Outbound message context
 */
final class OutboundMessageContext implements OutboundMessageContextInterface
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
     * Http request instance
     *
     * @var RequestInterface|null
     */
    private $request;

    /**
     * @inheritdoc
     */
    public static function fromIncoming(
        IncomingMessageContextInterface $incomingMessageContext,
        MessageSerializerInterface $messageSerializer
    ): self
    {
        $self = new self();

        $self->incomingMessageContext = $incomingMessageContext;
        $self->messageSerializer = $messageSerializer;

        return $self;
    }

    /**
     * @param RequestInterface           $request
     * @param HttpIncomingContext        $incomingMessageContext
     * @param MessageSerializerInterface $messageSerializer
     *
     * @return OutboundMessageContext
     */
    public static function fromHttpRequest(
        RequestInterface $request,
        HttpIncomingContext $incomingMessageContext,
        MessageSerializerInterface $messageSerializer
    ): self
    {
        $self = new self();

        $self->request = $request;
        $self->incomingMessageContext = $incomingMessageContext;
        $self->messageSerializer = $messageSerializer;

        return $self;
    }

    /**
     * @inheritdoc
     */
    public function publish(AbstractEvent $event, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->addToQueue($event, $messageDeliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function send(AbstractCommand $command, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->addToQueue($command, $messageDeliveryOptions);
    }

    /**
     * @inheritdoc
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
        $this->toPublishMessages = new \SplObjectStorage();
    }
}
