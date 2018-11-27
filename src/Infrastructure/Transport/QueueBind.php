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
final class QueueBind
{

    /**
     * The topic to which the binding is going
     *
     * @var Topic
     */
    private $topic;

    /**
     * Binding Key
     *
     * @var string|null
     */
    private $routingKey;

    /**
     * @param Topic       $topic      The topic to which the binding is going
     * @param string|null $routingKey Binding Key
     */
    public function __construct(Topic $topic, ?string $routingKey = null)
    {
        $this->topic      = $topic;
        $this->routingKey = $routingKey;
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
