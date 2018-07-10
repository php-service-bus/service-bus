<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus\Configuration;

/**
 * Handlers list
 */
final class MessageHandlerCollection implements \IteratorAggregate, \Countable
{
    /**
     * Message handlers
     *
     * @var array<mixed, \Desperado\ServiceBus\MessageBus\Configuration\MessageHandler>
     */
    private $collection;

    public function __construct()
    {
        $this->collection = [];
    }

    /**
     * Push handler to collection
     *
     * @param MessageHandler $handler
     *
     * @return void
     */
    public function push(MessageHandler $handler): void
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
