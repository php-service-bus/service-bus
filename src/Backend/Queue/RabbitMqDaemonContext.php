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

namespace Desperado\Framework\Backend\Queue;

use Bunny\Channel;
use Bunny\Message;
use Desperado\CQRS\Context\DeliveryContextInterface;
use Desperado\CQRS\Context\DeliveryOptions;
use Desperado\Domain\Messages\CommandInterface;
use Desperado\Domain\Messages\EventInterface;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\Domain\Serializer\MessageSerializerInterface;
use Desperado\Framework\Application\ApplicationLogger;

/**
 * RabbitMQ execution context
 */
class RabbitMqDaemonContext implements DeliveryContextInterface
{
    protected const LOG_CHANNEL_NAME = 'rabbitMqContext';

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $serializer;

    /**
     * Exchange ID
     *
     * @var string
     */
    private $exchange;

    /**
     * AMQP channel
     *
     * @var Channel
     */
    private $channel;

    /**
     * Routing key (client ID)
     *
     * @var string
     */
    private $routingKey;

    /**
     * @param Message                    $incoming
     * @param Channel                    $channel
     * @param MessageSerializerInterface $serializer
     */
    public function __construct(Message $incoming, Channel $channel, MessageSerializerInterface $serializer)
    {
        $this->exchange = $incoming->exchange;
        $this->routingKey = $incoming->routingKey;
        $this->channel = $channel;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function delivery(MessageInterface $message, DeliveryOptions $deliveryOptions = null): void
    {
        $deliveryOptions = $deliveryOptions ?? new DeliveryOptions();

        $this->publishMessage($deliveryOptions->getDestination(), $message);
    }

    /**
     * @inheritdoc
     */
    public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void
    {
        $this->publishMessage($deliveryOptions->getDestination(), $command);
    }

    /**
     * @inheritdoc
     */
    public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void
    {
        $this->publishMessage($deliveryOptions->getDestination(), $event);
        $this->publishMessage(\sprintf('%s.events', $this->exchange), $event);
    }

    /**
     * Send message to broker
     *
     * @param string           $destination
     * @param MessageInterface $message
     *
     * @return void
     */
    private function publishMessage(string $destination, MessageInterface $message)
    {
        $destination = '' !== $destination ? $destination : $this->exchange;
        $serializedMessage = $this->serializer->serialize($message);

        $this->channel
            ->exchangeDeclare($destination, 'direct', true)
            ->then(
                function() use ($destination, $serializedMessage, $message)
                {
                    ApplicationLogger::debug(
                        self::LOG_CHANNEL_NAME,
                        \sprintf(
                            'Publish message "%s" to "%s" destination with routing key "%s"',
                            \get_class($message), $destination, $this->routingKey
                        )
                    );

                    return $this->channel->publish($serializedMessage, [], $destination, $this->routingKey);
                },
                function(\Throwable $throwable)
                {
                    ApplicationLogger::throwable(self::LOG_CHANNEL_NAME, $throwable);
                }
            );
    }
}
