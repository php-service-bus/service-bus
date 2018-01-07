<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Task;

use Desperado\ServiceBus\Task\Task;
use Desperado\ServiceBus\Task\TaskOptions;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class TaskTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function execute(): void
    {
        $task = Task::new(
            \Closure::fromCallable(
                function()
                {
                    return '1';
                }
            ), TaskOptions::new()
        );
    }
}
