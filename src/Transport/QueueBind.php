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
final class QueueBind
{
    /**
     * @var Queue
     */
    private $queue;

    /**
     * @var Topic
     */
    private $topic;

    /**
     * @var string|null
     */
    private $routingKey;

    /**
     * @param Queue       $queue
     * @param Topic       $topic
     * @param string|null $routingKey
     */
    public function __construct(Queue $queue, Topic $topic, ?string $routingKey = null)
    {
        $this->queue = $queue;
        $this->topic = $topic;
        $this->routingKey = $routingKey;
    }

    /**
     * @return Queue
     */
    public function queue(): Queue
    {
        return $this->queue;
    }

    /**
     * @return Topic
     */
    public function topic(): Topic
    {
        return $this->topic;
    }

    /**
     * @return string|null
     */
    public function routingKey(): ?string
    {
        return $this->routingKey;
    }
}