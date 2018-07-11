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
 * Collection of arguments to the message handler
 */
final class MessageHandlerArgumentCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array<string, \Desperado\ServiceBus\MessageBus\Configuration\MessageHandlerArgument>
     */
    private $collection;

    public function __construct()
    {
        $this->collection = [];
    }

    /**
     * @param MessageHandlerArgument $argument
     *
     * @return void
     */
    public function push(MessageHandlerArgument $argument): void
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
