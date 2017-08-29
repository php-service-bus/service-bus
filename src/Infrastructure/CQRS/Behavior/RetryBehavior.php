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

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Behavior;

use Desperado\ConcurrencyFramework\Domain\Behavior\BehaviorInterface;
use Desperado\ConcurrencyFramework\Domain\Task\TaskInterface;

/**
 * Retry failed commands
 */
class RetryBehavior implements BehaviorInterface
{
    /**
     * @param TaskInterface $task
     *
     * @return void
     */
    public function __invoke(TaskInterface $task): void
    {

    }
}
