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
     * Routes to which messages will be sent
     *
     * [
     *     'SomeClassNamespace' => Destination[]
     * ]
     *
     * @var array<string, \Desperado\ServiceBus\OutboundMessage\Destination>
     */
    private $destinations;

    /**
     * Default destinations
     * In them, messages will be sent regardless of the availability of individual routes
     *
     * @var array<mixed, \Desperado\ServiceBus\OutboundMessage\Destination>
     */
    private $defaultDestinations;

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
        return \array_merge(
            $this->defaultDestinations,
            $this->destinations[$messageClass] ?? []
        );
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
     * Add directions for a specific message (in addition to the default directions)
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
        $this->destinations[$messageClass][] = $destination;
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
