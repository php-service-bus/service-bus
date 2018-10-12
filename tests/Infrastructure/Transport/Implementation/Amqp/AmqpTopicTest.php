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
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class AmqpTopicTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function fanoutCreate(): void
    {
        $exchange = AmqpExchange::fanout('fanoutName');

        static::assertEquals('fanout', $exchange->type());
        static::assertEquals('fanoutName', (string) $exchange);
    }

    /**
     * @test
     *
     * @return void
     */
    public function directCreate(): void
    {
        $exchange = AmqpExchange::direct('directName');

        static::assertEquals('direct', $exchange->type());
        static::assertEquals('directName', (string) $exchange);
    }

    /**
     * @test
     *
     * @return void
     */
    public function topicCreate(): void
    {
        $exchange = AmqpExchange::topic('topicName');

        static::assertEquals('topic', $exchange->type());
        static::assertEquals('topicName', (string) $exchange);
    }

    /**
     * @test
     *
     * @return void
     */
    public function delayedCreate(): void
    {
        $exchange = AmqpExchange::delayed('delayedName');

        static::assertEquals('x-delayed-message', $exchange->type());
        static::assertEquals('delayedName', (string) $exchange);

        static::assertEquals(['x-delayed-type' => 'direct'], $exchange->arguments());
    }

    /**
     * @test
     *
     * @return void
     */
    public function flags(): void
    {
        $exchange = AmqpExchange::direct('directName', true);

        /** @see AmqpExchange::AMQP_DURABLE */
        static::assertEquals(2, $exchange->flags());

        /** @see AmqpExchange::AMQP_PASSIVE */
        $exchange->makePassive();
        static::assertEquals(6, $exchange->flags());


        $exchange->wthArguments(['key' => 'value']);
        static::assertEquals(['key' => 'value'], $exchange->arguments());
    }
}
