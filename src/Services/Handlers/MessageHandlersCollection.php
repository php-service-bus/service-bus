<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Handlers;

/**
 * Collection of message handlers
 */
class MessageHandlersCollection implements \IteratorAggregate, \Countable
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
     * @return MessageHandlersCollection
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        return yield from $this->collection;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return \count($this->collection);
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
