<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\Backend\RabbitMQ;

use Bunny\Async\Client;
use Bunny\Channel;
use Bunny\Message;
use Desperado\ConcurrencyFramework\Common\Formatter\ThrowableFormatter;
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryOptions;
use Psr\Log\LoggerInterface;

/**
 * ReactPHP RabbitMQ context
 */
class RabbitMqContext implements DeliveryContextInterface
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
        $this->channel = $channel;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void
    {
        //$this->publishMessage($deliveryOptions->getDestination(), $command);
    }

    /**
     * @inheritdoc
     */
    public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void
    {
        $this->publishMessage($this->exchange, $event);
        $this->publishMessage(\sprintf('%s.events', $this->exchange), $event);
    }

    private function publishMessage(string $destination, MessageInterface $message)
    {
        $serializedMessage = $this->serializer->serialize($message);

        $this->channel
            ->exchangeDeclare($destination, 'direct', true)
            ->then(
                function() use ($destination, $serializedMessage)
                {
                    $this->channel->publish($serializedMessage, [], $destination);
                },
                function(\Throwable $throwable)
                {
                    $this->logger->critical(ThrowableFormatter::toString($throwable));
                }
            );
    }
}
