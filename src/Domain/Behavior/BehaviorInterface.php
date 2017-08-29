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

namespace Desperado\ConcurrencyFramework\Domain\Behavior;

use Desperado\ConcurrencyFramework\Domain\Pipeline\PipelineInterface;
use Desperado\ConcurrencyFramework\Domain\Task\TaskInterface;

/**
 * Behavior
 */
interface BehaviorInterface
{
    /**
     * Execute
     *
     * @param PipelineInterface $pipeline
     * @param TaskInterface     $task
     *
     * @return void
     */
    public function __invoke(PipelineInterface $pipeline, TaskInterface $task): void;
}
