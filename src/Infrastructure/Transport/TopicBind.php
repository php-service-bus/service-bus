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

namespace Desperado\ServiceBus\Infrastructure\Transport;

/**
 *
 */
final class TopicBind
{
    /**
     * The topic to which the binding is going
     *
     * @var Topic
     */
    private $destinationTopic;

    /**
     * Binding Key
     *
     * @var string|null
     */
    private $routingKey;

    /**
     * @param Topic       $destinationTopic
     * @param string|null $routingKey
     */
    public function __construct(Topic $destinationTopic, ?string $routingKey = null)
    {
        $this->destinationTopic = $destinationTopic;
        $this->routingKey       = $routingKey;
    }

    /**
     * @return Topic
     */
    public function topic(): Topic
    {
        return $this->destinationTopic;
    }

    /**
     * @return string|null
     */
    public function routingKey(): ?string
    {
        return $this->routingKey;
    }
}
