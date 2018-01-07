<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Handlers\Messages;

/**
 * Collection of message handlers
 */
class MessageHandlersCollection implements \IteratorAggregate
{
    /**
     * Collection of message handlers
     *
     * @var MessageHandlerData[]
     */
    private $collection;

    /**
     * Create collection
     *
     * @param MessageHandlerData[] $handlers
     *
     * @return MessageHandlersCollection
     */
    public static function create(array $handlers = []): self
    {
        $self = new self();

        foreach($handlers as $handler)
        {
            $self->add($handler);
        }

        return $self;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        return yield from $this->collection;
    }

    /**
     * Add to collection
     *
     * @param MessageHandlerData $messageHandlerData
     *
     * @return void
     */
    public function add(MessageHandlerData $messageHandlerData): void
    {
        $this->collection[] = $messageHandlerData;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {
        $this->collection = [];
    }
}
