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

namespace Desperado\ServiceBus\MessageBus\Processor;

/**
 * Tasks list
 */
final class ProcessorsMap implements \Countable
{
    /**
     * @var array
     */
    private $collection = [];

    /**
     * Is there a handler for the specified message
     *
     * @param string $messageClass
     *
     * @return bool
     */
    public function hasTask(string $messageClass): bool
    {
        return isset($this->collection[$messageClass]);
    }

    /**
     * @param string    $messageClass
     * @param Processor $task
     *
     * @return void
     */
    public function push(string $messageClass, Processor $task): void
    {
        $this->collection[$messageClass][] = $task;
    }

    /**
     * Receive handlers for specified message
     *
     * @param string $messageClass
     *
     * @return array<mixed, \Desperado\ServiceBus\MessageBus\Processor\Processor>
     */
    public function map(string $messageClass): array
    {
        return $this->collection[$messageClass] ?? [];
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return (int) \array_sum(
            \array_map(
                static function(array $eachMessage): int
                {
                    return \count($eachMessage);
                },
                $this->collection
            )
        );
    }
}
