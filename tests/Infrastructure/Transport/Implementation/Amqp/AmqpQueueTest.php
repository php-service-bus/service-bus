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

use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpExchange;
use Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp\AmqpQueue;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AmqpQueueTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function defaultCreate(): void
    {
        $queue = AmqpQueue::default(__METHOD__, false);

        static::assertEquals(__METHOD__, (string) $queue);

        static::assertEquals(0, $queue->flags());

    }

    /**
     * @test
     *
     * @return void
     */
    public function delayedCreate(): void
    {
        $queue = AmqpQueue::delayed('test', AmqpExchange::direct('qwerty'));

        static::assertEquals('test', (string) $queue);

        /** @see AmqpQueue::AMQP_DURABLE */
        static::assertEquals(2, $queue->flags());
    }

    /**
     * @test
     *
     * @return void
     */
    public function flags(): void
    {
        $queue = AmqpQueue::default(__METHOD__, true);

        /** @see AmqpQueue::AMQP_DURABLE */
        static::assertEquals(2, $queue->flags());


        /** @see AmqpQueue::AMQP_PASSIVE */
        $queue->makePassive();
        static::assertEquals(6, $queue->flags());


        /** @see AmqpQueue::AMQP_AUTO_DELETE */
        $queue->enableAutoDelete();
        static::assertEquals(22, $queue->flags());

        /** @see AmqpQueue::AMQP_EXCLUSIVE */
        $queue->makeExclusive();
        static::assertEquals(30, $queue->flags());


        $queue->wthArguments(['key' => 'value']);
        static::assertEquals(['key' => 'value'], $queue->arguments());
    }
}
