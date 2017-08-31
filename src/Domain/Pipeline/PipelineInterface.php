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

namespace Desperado\ConcurrencyFramework\Domain\Pipeline;

use Desperado\ConcurrencyFramework\Domain\Task\TaskInterface;

/**
 * Pipeline
 */
interface PipelineInterface
{
    /**
     * Get pipeline name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Push task to queue
     *
     * @param TaskInterface $task
     *
     * @return $this
     */
    public function push(TaskInterface $task);

    /**
     * Push task collection to queue
     *
     * @param iterable $taskCollection
     *
     * @return $this
     */
    public function pushCollection(iterable $taskCollection);

    /**
     * Execute task
     *
     * @return \Generator
     */
    public function run(): \Generator;
}
