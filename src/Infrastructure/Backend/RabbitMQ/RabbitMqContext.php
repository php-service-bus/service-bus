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
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryOptions;
use React\EventLoop\LoopInterface;

/**
 * ReactPHP RabbitMQ context
 */
class RabbitMqContext implements DeliveryContextInterface
{
    /**
     * Publisher client
     *
     * @var Client
     */
    private $publisher;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $serializer;

    /**
     * @param LoopInterface              $eventLoop
     * @param array                      $configParts
     * @param MessageSerializerInterface $serializer
     */
    public function __construct(
        LoopInterface $eventLoop,
        array $configParts,
        MessageSerializerInterface $serializer
    )
    {
        $this->publisher = new Client($eventLoop, $configParts);
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void
    {
        $this->publisher->publish($deliveryOptions->getDestination(), $this->serializer->serialize($command));
    }

    /**
     * @inheritdoc
     */
    public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void
    {
        $this->publisher->publish($deliveryOptions->getDestination(), $this->serializer->serialize($event));
    }
}
