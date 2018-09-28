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

namespace Desperado\ServiceBus\Tests\EventSourcing;

use Desperado\ServiceBus\Tests\Stubs\EventSourcing\TestAggregateId;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AggregateIdTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\EventSourcing\Exceptions\EmptyAggregateIdentifierNotAllowed
     * @expectedExceptionMessage The aggregate identifier can't be empty
     *
     * @return void
     */
    public function createWithEmptyId(): void
    {
        new TestAggregateId('');
    }
}
