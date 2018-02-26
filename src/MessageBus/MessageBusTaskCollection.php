<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageBus;

/**
 * Collection of tasks
 */
final class MessageBusTaskCollection implements \Countable
{
    /**
     * Collection of tasks
     *
     * @var MessageBusTask[]
     */
    private $collection;

    /**
     * Create empty collection instance
     *
     * @return self
     */
    public static function createEmpty(): self
    {
        return new self();
    }

    /**
     * @param MessageBusTask[] $collectionData
     *
     * @return self
     */
    public static function createFromArray(array $collectionData): self
    {
        $self = new self();

        foreach($collectionData as $task)
        {
            $self->add($task);
        }

        return $self;
    }

    /**
     * Add to collection
     *
     * @param MessageBusTask $task
     *
     * @return void
     */
    public function add(MessageBusTask $task): void
    {
        if(false === $this->has($task))
        {
            $this->collection[\spl_object_hash($task)] = $task;
        }
    }

    /**
     * Is the task added to the collection?
     *
     * @param MessageBusTask $task
     *
     * @return bool
     */
    public function has(MessageBusTask $task): bool
    {
        return isset($this->collection[\spl_object_hash($task)]);
    }

    /**
     * Get all handlers for the specified message namespace
     *
     * @param string $messageNamespace
     *
     * @return MessageBusTask[]
     */
    public function mapByMessageNamespace(string $messageNamespace): array
    {
        return \array_filter(
            \array_map(
                function(MessageBusTask $busTask) use ($messageNamespace)
                {
                    return $messageNamespace === $busTask->getMessageNamespace()
                        ? $busTask
                        : null;
                },
                $this->collection
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return \count($this->collection);
    }

    /**
     * Close constructor
     */
    private function __construct()
    {
        $this->collection = [];
    }
}
