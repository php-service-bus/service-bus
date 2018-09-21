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

namespace Desperado\ServiceBus\Tests\Transport\Amqp;

use Desperado\ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AmqpConnectionConfigurationTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function createLocalhost(): void
    {
        $options = AmqpConnectionConfiguration::createLocalhost();

        static::assertEquals(
            'amqp://guest:guest@localhost:5672?vhost=/&timeout=1&heartbeat=60.00',
            (string) $options
        );

        static::assertEquals('localhost', $options->host());
        static::assertEquals(5672, $options->port());
        static::assertEquals('/', $options->virtualHost());
        static::assertEquals('guest', $options->password());
        static::assertEquals('guest', $options->user());
        static::assertEquals(1.0, $options->timeout());
        static::assertEquals(60.0, $options->heartbeatInterval());
    }

    /**
     * @test
     *
     * @return void
     */
    public function parseDSN(): void
    {
        static::assertEquals(
            AmqpConnectionConfiguration::createLocalhost(),
            new AmqpConnectionConfiguration(
                'amqp://guest:guest@localhost:5672?vhost=/&timeout=1&heartbeat=60.00'
            )
        );
    }
}
