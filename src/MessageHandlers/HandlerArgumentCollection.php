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
 * Collection of arguments to the message handler
 */
final class HandlerArgumentCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var \SplObjectStorage<\Desperado\ServiceBus\MessageHandlers\HandlerArgument>
     */
    private $collection;

    public function __construct()
    {
        $this->collection = new \SplObjectStorage();
    }

    /**
     * @param HandlerArgument $argument
     *
     * @return void
     */
    public function push(HandlerArgument $argument): void
    {
        if(false === $this->collection->contains($argument))
        {
            $this->collection->attach($argument);
        }
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
        return $this->collection->count();
    }
}
