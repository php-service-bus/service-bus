<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Domain\Behavior;

use Desperado\Framework\Domain\Pipeline\PipelineInterface;
use Desperado\Framework\Domain\Task\TaskInterface;

/**
 * Behavior
 */
interface BehaviorInterface
{
    /**
     * Apply behavior
     *
     * @param PipelineInterface $pipeline
     * @param TaskInterface     $task
     *
     * @return TaskInterface
     */
    public function apply(PipelineInterface $pipeline, TaskInterface $task): TaskInterface;
}
