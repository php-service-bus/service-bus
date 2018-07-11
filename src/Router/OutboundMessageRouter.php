<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Router;

use Desperado\ServiceBus\Router\Exceptions\EmptyDestinationTopicSpecified;
use Desperado\ServiceBus\Router\Exceptions\MessageClassCantBeEmpty;
use Desperado\ServiceBus\Transport\Destination;

/**
 * Routes to which messages will be sent
 */
class OutboundMessageRouter
{
    /**
     * Routes to which messages will be sent
     *
     * [
     *     'SomeClassNamespace' => Destination[]
     * ]
     *
     * @var array<string, \Desperado\ServiceBus\Transport\Destination>
     */
    private $destinations;

    /**
     * Default destinations
     * In them, messages will be sent regardless of the availability of individual routes
     *
     * @var array<mixed, \Desperado\ServiceBus\Transport\Destination>
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
     * @return array<mixed, \Desperado\ServiceBus\Transport\Destination>
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
     * @param array<mixed, \Desperado\ServiceBus\Transport\Destination> $destinations
     *
     * @return void
     */
    public function addDefaultRoutes(array $destinations): void
    {
        $this->defaultDestinations = $destinations;
    }

    /**
     * @param string      $messageClass
     * @param Destination $destination
     *
     * @return void
     *
     * @throws  \Desperado\ServiceBus\Router\Exceptions\MessageClassCantBeEmpty
     *
     * @throws \Desperado\ServiceBus\Router\Exceptions\EmptyDestinationTopicSpecified
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
     * @throws \Desperado\ServiceBus\Router\Exceptions\MessageClassCantBeEmpty
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
     * @throws \Desperado\ServiceBus\Router\Exceptions\EmptyDestinationTopicSpecified
     */
    private static function validateDestination(Destination $destination): void
    {
        if('' === (string) $destination->topicName())
        {
            throw new EmptyDestinationTopicSpecified();
        }
    }
}
