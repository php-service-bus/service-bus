<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Application\Queue\RabbitMQ;

use Bunny\Channel;
use Bunny\Message;
use Desperado\Framework\Common\Formatter\ThrowableFormatter;
use Desperado\Framework\Domain\Messages\CommandInterface;
use Desperado\Framework\Domain\Messages\EventInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Domain\Serializer\MessageSerializerInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryOptions;
use Psr\Log\LoggerInterface;

/**
 * Execution context RabbitMQ
 */
class RabbitMqDaemonContext implements DeliveryContextInterface
{
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
     * Logger
     *
     * @var LoggerInterface
     */
    private $logger;

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
     * @param LoggerInterface            $logger
     */
    public function __construct(
        Message $incoming,
        Channel $channel,
        MessageSerializerInterface $serializer,
        LoggerInterface $logger
    )
    {
        $this->exchange = $incoming->exchange;
        $this->routingKey = $incoming->routingKey;
        $this->channel = $channel;
        $this->serializer = $serializer;
        $this->logger = $logger;
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
                    $this->logger->debug(
                        \sprintf(
                            'Publish message "%s" to "%s" destination with routing key "%s"',
                            \get_class($message), $destination, $this->routingKey
                        )
                    );

                    return $this->channel->publish($serializedMessage, [], $destination, $this->routingKey);
                },
                function(\Throwable $throwable)
                {
                    $this->logger->critical(ThrowableFormatter::toString($throwable));
                }
            );
    }
}
