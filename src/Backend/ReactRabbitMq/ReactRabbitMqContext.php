<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Backend\ReactRabbitMq;

use Bunny\Channel;
use Bunny\Message;
use Desperado\CQRS\Context\DeliveryContextInterface;
use Desperado\CQRS\Context\DeliveryOptions;
use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\ParameterBag;

/**
 * RabbitMQ execution context
 */
class ReactRabbitMqContext implements DeliveryContextInterface
{
    /**
     * Publisher
     *
     * @var ReactRabbitMqPublisher
     */
    private $publisher;

    /**
     * Incoming message
     *
     * @var Message
     */
    private $incomingMessage;

    /**
     * AMQP channel
     *
     * @var Channel
     */
    private $channel;

    /**
     * @param ReactRabbitMqPublisher $publisher
     * @param Message                $incomingMessage
     * @param Channel                $channel
     */
    public function __construct(ReactRabbitMqPublisher $publisher, Message $incomingMessage, Channel $channel)
    {
        $this->publisher = $publisher;
        $this->incomingMessage = $incomingMessage;
        $this->channel = $channel;
    }

    /**
     * @inheritdoc
     */
    public function getMessageMetadata(): ParameterBag
    {
        return new ParameterBag(
            true === \is_array($this->incomingMessage->headers)
                ? $this->incomingMessage->headers
                : []
        );
    }

    /**
     * @inheritdoc
     */
    public function delivery(AbstractMessage $message, DeliveryOptions $deliveryOptions = null): void
    {
        $deliveryOptions = $deliveryOptions ?? new DeliveryOptions();

        $message instanceof AbstractCommand
            ? $this->send($message, $deliveryOptions)
            /** @var AbstractEvent $message */
            : $this->publish($message, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function send(AbstractCommand $command, DeliveryOptions $deliveryOptions): void
    {
        $this->publishMessage($command, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function publish(AbstractEvent $event, DeliveryOptions $deliveryOptions): void
    {
        $this->publishMessage($event, $deliveryOptions);
    }

    /**
     * Send message to broker
     *
     * @param AbstractMessage  $message
     * @param  DeliveryOptions $deliveryOptions
     *
     * @return void
     */
    private function publishMessage(AbstractMessage $message, DeliveryOptions $deliveryOptions): void
    {
        $this->publisher->publish(
            $this->incomingMessage,
            $this->channel,
            $message,
            $deliveryOptions
        );
    }
}
