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

namespace Desperado\ServiceBus\Transport;

/**
 * Information about where to send a message
 */
final class Destination
{
    /**
     * Topic name
     *
     * @var string|null
     */
    private $topic;

    /**
     * Topic\queue routing key
     *
     * @var string|null
     */
    private $routingKey;

    /**
     * @param string|null $topic
     * @param string|null $routingKey
     */
    public function __construct(?string $topic, ?string $routingKey)
    {
        $this->topic      = $topic;
        $this->routingKey = $routingKey;
    }

    /**
     * Receive topic name
     *
     * @return string|null
     */
    public function topicName(): ?string
    {
        return $this->topic;
    }

    /**
     * Receive routing key
     *
     * @return string|null
     */
    public function routingKey(): ?string
    {
        return $this->routingKey;
    }
}
