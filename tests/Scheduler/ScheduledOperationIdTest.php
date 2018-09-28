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

namespace Desperado\ServiceBus\Tests\Scheduler;

use Desperado\ServiceBus\Scheduler\ScheduledOperationId;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class ScheduledOperationIdTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Scheduler\Exceptions\EmptyScheduledOperationIdentifierNotAllowed
     * @expectedExceptionMessage Scheduled operation identifier can't be empty
     *
     * @return void
     */
    public function createWithEmptyId(): void
    {
        new ScheduledOperationId('');
    }
}
