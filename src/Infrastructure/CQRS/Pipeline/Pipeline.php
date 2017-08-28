<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Pipeline;

use Desperado\ConcurrencyFramework\Domain\Pipeline\PipelineEntry;
use Desperado\ConcurrencyFramework\Domain\Pipeline\PipelineInterface;
use Desperado\ConcurrencyFramework\Domain\Task\TaskInterface;

/**
 * Pipeline
 */
class Pipeline implements PipelineInterface
{
    /**
     * Pipeline name
     *
     * @var string
     */
    private $name;

    /**
     * Task list
     *
     * @var \SplDoublyLinkedList()
     */
    private $queue;

    /**
     * Failed task list
     *
     * @var \SplObjectStorage
     */
    private $failed;

    /**
     * @param string        $name
     * @param iterable|null $taskCollection
     */
    public function __construct(string $name, iterable $taskCollection = null)
    {
        $this->name = $name;
        $this->queue = new \SplDoublyLinkedList();
        $this->queue->setIteratorMode(
            \SplDoublyLinkedList::IT_MODE_FIFO |
            \SplDoublyLinkedList::IT_MODE_DELETE
        );

        if(null !== $taskCollection)
        {
            $this->pushCollection($taskCollection);
        }

        $this->failed = new \SplObjectStorage();
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function push(TaskInterface $task): self
    {
        $this->queue->push($task);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function pushCollection(iterable $taskCollection): self
    {
        foreach($taskCollection as $task)
        {
            true === \is_iterable($task)
                ? $this->pushCollection($task)
                : $this->push($task);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function run(): \Generator
    {
        $queue = clone $this->queue;

        while(false === $this->queue->isEmpty())
        {
            try
            {
                /** @var PipelineEntry $entry */
                $entry = yield;

                /** @var TaskInterface $task */
                $task = $this->queue->shift();

                $result = $task($entry->getMessage(), $entry->getContext());

                if(null !== $result && $result instanceof TaskInterface)
                {
                    $this->push($result);
                }
            }
            catch(\Throwable $throwable)
            {
                $this->failed->attach($task, $throwable);
            }
        }

        $this->queue = $queue;
    }
}
