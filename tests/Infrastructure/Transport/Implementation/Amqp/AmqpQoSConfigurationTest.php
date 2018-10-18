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

namespace Desperado\ServiceBus\Tests\Infrastructure\Transport\Implementation\Amqp;

use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQoSConfiguration;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AmqpQoSConfigurationTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function successCreate(): void
    {
        $qos = new AmqpQoSConfiguration(1, 6, true);

        static::assertEquals(1, $qos->qosSize());
        static::assertEquals(6, $qos->qosCount());
        static::assertTrue($qos->isGlobal());
    }
}
