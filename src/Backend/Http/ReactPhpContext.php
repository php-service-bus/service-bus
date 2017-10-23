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

namespace Desperado\Framework\Backend\Http;

use Desperado\CQRS\Context\DeliveryContextInterface;
use Desperado\CQRS\Context\DeliveryOptions;
use Desperado\Domain\Messages\CommandInterface;
use Desperado\Domain\Messages\EventInterface;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\Domain\Serializer\MessageSerializerInterface;
use Desperado\Infrastructure\Bridge\Publisher\PublisherInterface;

/**
 * ReactPHP execution context
 */
class ReactPhpContext implements DeliveryContextInterface
{
    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $serializer;

    /**
     * Publisher instance
     *
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Routing key
     *
     * @var string
     */
    private $routingKey;

    /**
     * @param MessageSerializerInterface $serializer
     * @param PublisherInterface         $publisher
     * @param string                     $entryPointName
     * @param string                     $routingKey
     */
    public function __construct(
        MessageSerializerInterface $serializer,
        PublisherInterface $publisher,
        string $entryPointName,
        string $routingKey
    )
    {
        $this->serializer = $serializer;
        $this->publisher = $publisher;
        $this->entryPointName = $entryPointName;
        $this->routingKey = $routingKey;
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
        $this->publishMessage(\sprintf('%s.events', $this->entryPointName), $event);
    }

    /**
     * Send message to broker
     *
     * @param string           $destination
     * @param MessageInterface $message
     *
     * @return void
     */
    private function publishMessage(string $destination, MessageInterface $message): void
    {
        $destination = '' !== $destination ? $destination : $this->entryPointName;
        $serializedMessage = $this->serializer->serialize($message);

        $this->publisher->publish($destination, $this->routingKey, $serializedMessage);
    }
}
