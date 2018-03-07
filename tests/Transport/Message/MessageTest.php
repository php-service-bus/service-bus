<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Tests\Transport\Message;

use Desperado\Domain\ParameterBag;
use Desperado\ServiceBus\Transport\Message\Message;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class MessageTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     *
     * @throws \Exception
     */
    public function create(): void
    {
        $message = Message::create(
            'messageBody',
            new ParameterBag(),
            'exchangeKey',
            'routingKey'
        );

        static::assertEquals('messageBody', $message->getBody());
        static::assertEquals('exchangeKey', $message->getExchange());
        static::assertEquals('routingKey', $message->getRoutingKey());
        static::assertEquals(new ParameterBag(), $message->getHeaders());
    }
}
