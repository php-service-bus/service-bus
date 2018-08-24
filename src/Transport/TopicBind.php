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
 *
 */
final class TopicBind
{
    /**
     * @var Topic
     */
    private $sourceTopic;

    /**
     * @var Topic
     */
    private $destinationTopic;

    /**
     * @var string|null
     */
    private $routingKey;

    /**
     * @param Topic       $topic
     * @param string|null $routingKey
     */
    public function __construct(Topic $sourceTopic, Topic $destinationTopic, ?string $routingKey = null)
    {
        $this->sourceTopic      = $sourceTopic;
        $this->destinationTopic = $destinationTopic;
        $this->routingKey = $routingKey;
    }

    /**
     * @return Topic
     */
    public function sourceTopic(): Topic
    {
        return $this->sourceTopic;
    }

    /**
     * @return Topic
     */
    public function destinationTopic(): Topic
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
