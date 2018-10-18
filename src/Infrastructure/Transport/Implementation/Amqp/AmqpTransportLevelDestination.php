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

namespace Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp;

use Desperado\ServiceBus\Endpoint\TransportLevelDestination;

/**
 * Which exchange (and with which key) the message will be sent to
 */
final class AmqpTransportLevelDestination implements TransportLevelDestination
{
    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string|null
     */
    private $routingKey;

    /**
     * @param string      $exchange
     * @param string|null $routingKey
     */
    public function __construct(string $exchange, ?string $routingKey)
    {
        $this->exchange   = $exchange;
        $this->routingKey = $routingKey;
    }

    /**
     * @return string
     */
    public function exchange(): string
    {
        return $this->exchange;
    }

    /**
     * @return string|null
     */
    public function routingKey(): ?string
    {
        return $this->routingKey;
    }
}
