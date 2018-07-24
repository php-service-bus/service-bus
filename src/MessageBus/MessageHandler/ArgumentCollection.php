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

namespace Desperado\ServiceBus\MessageBus\MessageHandler;

/**
 * Collection of arguments to the message handler
 */
final class ArgumentCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, \Desperado\ServiceBus\MessageBus\MessageHandler\Argument>
     */
    private $collection;

    public function __construct()
    {
        $this->collection = [];
    }

    /**
     * @param Argument $argument
     *
     * @return void
     */
    public function push(Argument $argument): void
    {
        $this->collection[\spl_object_hash($argument)] = $argument;
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
