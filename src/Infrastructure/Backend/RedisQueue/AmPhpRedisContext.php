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

namespace Desperado\ConcurrencyFramework\Infrastructure\Backend\RedisQueue;

use Amp\Redis\Client;
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryOptions;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\Exceptions\EmptyMessageDestinationException;

/**
 * AMPHP redis execution context
 */
class AmPhpRedisContext implements DeliveryContextInterface
{
    /**
     * Redis publisher
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
     * @param Client                     $publisher
     * @param MessageSerializerInterface $serializer
     */
    public function __construct(Client $publisher, MessageSerializerInterface $serializer)
    {
        $this->publisher = $publisher;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void
    {
        self::guardDestination($command, $deliveryOptions);

        $this->publisher->publish($deliveryOptions->getDestination(), $this->serializer->serialize($command));
    }

    /**
     * @inheritdoc
     */
    public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void
    {
        self::guardDestination($event, $deliveryOptions);

        $this->publisher->publish($deliveryOptions->getDestination(), $this->serializer->serialize($event));
    }

    /**
     * Assert message destination specified
     *
     * @param MessageInterface $message
     * @param DeliveryOptions  $deliveryOptions
     *
     * @return void
     *
     * @throws EmptyMessageDestinationException
     */
    private static function guardDestination(MessageInterface $message, DeliveryOptions $deliveryOptions)
    {
        if(false === $deliveryOptions->destinationSpecified())
        {
            throw new EmptyMessageDestinationException(
                \sprintf(
                    'Can\'t find destination exchange for message "%s"', \get_class($message)
                )
            );
        }
    }
}
