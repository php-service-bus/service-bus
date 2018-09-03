<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\OutboundMessage;

use Desperado\ServiceBus\OutboundMessage\Exceptions\EmptyDestinationTopicSpecified;
use Desperado\ServiceBus\OutboundMessage\Exceptions\MessageClassCantBeEmpty;

/**
 * Routes to which messages will be sent (Directions are indicated in the context of the current transport)
 *
 * @todo: Direction in the context of any configured transport
 */
final class OutboundMessageRoutes
{
    /**
     * Routes for specific messages
     * If the message has its own route, it will not be sent to the default route
     *
     * [
     *     'SomeClassNamespace' => Destination[]
     * ]
     *
     * @var array<string, \Desperado\ServiceBus\OutboundMessage\Destination>
     */
    private $customDestinations = [];

    /**
     * The default routes for messages
     *
     * @var array<mixed, \Desperado\ServiceBus\OutboundMessage\Destination>
     */
    private $defaultDestinations = [];

    /**
     * Receive destinations for specified message
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @param string $messageClass
     *
     * @return array<mixed, \Desperado\ServiceBus\OutboundMessage\Destination>
     */
    public function destinationsFor(string $messageClass): array
    {
        if(
            true === isset($this->customDestinations[$messageClass]) &&
            true === \is_array($this->customDestinations[$messageClass]) &&
            0 !== \count($this->customDestinations[$messageClass])
        )
        {
            return $this->customDestinations[$messageClass];
        }

        return $this->defaultDestinations;
    }

    /**
     * Add default destinations
     *
     * @param array<mixed, \Desperado\ServiceBus\OutboundMessage\Destination> $destinations
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\OutboundMessage\Exceptions\MessageClassCantBeEmpty
     */
    public function addDefaultRoutes(array $destinations): void
    {
        foreach($destinations as $destination)
        {
            /** @var Destination $destination */
            self::validateDestination($destination);
        }

        $this->defaultDestinations = $destinations;
    }

    /**
     * Add directions for a specific message
     *
     * @param string      $messageClass
     * @param Destination $destination
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\OutboundMessage\Exceptions\MessageClassCantBeEmpty
     * @throws \Desperado\ServiceBus\OutboundMessage\Exceptions\EmptyDestinationTopicSpecified
     */
    public function addRoute(string $messageClass, Destination $destination): void
    {
        self::validateMessageClass($messageClass);
        self::validateDestination($destination);

        /** @psalm-suppress InvalidArrayAssignment */
        $this->customDestinations[$messageClass][] = $destination;
    }

    /**
     * @param string $messageClass
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\OutboundMessage\Exceptions\MessageClassCantBeEmpty
     */
    private static function validateMessageClass(string $messageClass): void
    {
        if('' === $messageClass)
        {
            throw new MessageClassCantBeEmpty();
        }
    }

    /**
     * Validate destination parameters
     *
     * @param Destination $destination
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\OutboundMessage\Exceptions\EmptyDestinationTopicSpecified
     */
    private static function validateDestination(Destination $destination): void
    {
        if('' === (string) $destination->topicName())
        {
            throw new EmptyDestinationTopicSpecified();
        }
    }
}
