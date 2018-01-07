<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Task\Behaviors;

use Desperado\ServiceBus\Task\TaskInterface;

/**
 * Behavior interface
 */
interface BehaviorInterface
{
    /**
     * Apply behavior
     *
     * @param TaskInterface $task
     *
     * @return TaskInterface
     */
    public function apply(TaskInterface $task): TaskInterface;
}
