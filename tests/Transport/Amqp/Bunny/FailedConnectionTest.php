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

namespace Desperado\ServiceBus\Tests\Transport\Amqp\Bunny;

use Desperado\ServiceBus\Transport\Amqp\AmqpConnectionConfiguration;
use Desperado\ServiceBus\Transport\Amqp\Bunny\AmqpBunny;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class FailedConnectionTest extends TestCase
{

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     * @expectedExceptionMessage Broken pipe or closed connection.
     *
     * @return void
     */
    public function failedConnection(): void
    {
        new AmqpBunny(new AmqpConnectionConfiguration('amqp://guest1:guest1@localhost:5672'));
    }
}
