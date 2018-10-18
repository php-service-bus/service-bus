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

namespace Desperado\ServiceBus\MessageHandlers;

/**
 * Handlers list
 */
final class HandlerCollection implements \IteratorAggregate, \Countable
{
    /**
     * Message handlers
     *
     * @var array<mixed, \Desperado\ServiceBus\MessageHandlers\Handler>
     */
    private $collection;

    public function __construct()
    {
        $this->collection = [];
    }

    /**
     * Push handler to collection
     *
     * @param Handler $handler
     *
     * @return void
     */
    public function push(Handler $handler): void
    {
        $this->collection[\spl_object_hash($handler)] = $handler;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        yield from $this->collection;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return \count($this->collection);
    }
}
