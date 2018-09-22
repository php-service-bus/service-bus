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

use PHPUnit\Framework\TestCase;
use Desperado\ServiceBus\Transport\Amqp\AmqpOutboundEnvelope;

/**
 *
 */
final class AmqpOutboundEnvelopeTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function successCreate(): void
    {
        $envelope = new AmqpOutboundEnvelope('body', ['headerKey' => 'headerValue']);

        static::assertEquals('application/json', $envelope->contentType());
        $envelope->changeContentType('application/xml');
        static::assertEquals('application/xml', $envelope->contentType());


        static::assertEquals('UTF-8', $envelope->contentEncoding());
        $envelope->changeContentEncoding('windows-1251');
        static::assertEquals('windows-1251',$envelope->contentEncoding());


        static::assertFalse($envelope->isMandatory());
        $envelope->makeMandatory();
        static::assertTrue($envelope->isMandatory());


        static::assertFalse($envelope->isImmediate());
        $envelope->makeImmediate();
        static::assertTrue($envelope->isImmediate());


        static::assertFalse($envelope->isPersistent());
        $envelope->makePersistent();
        static::assertTrue($envelope->isPersistent());


        static::assertEquals(0, $envelope->priority());
        $envelope->changePriority(10);
        static::assertEquals(10, $envelope->priority());


        static::assertNull($envelope->expirationTime());
        $envelope->makeExpiredAfter(10000);
        static::assertEquals(10000, $envelope->expirationTime());


        static::assertNull($envelope->clientId());
        $envelope->setupClientId('someClientId');
        static::assertEquals('someClientId', $envelope->clientId());


        static::assertNull($envelope->appId());
        $envelope->setupAppId('someAppId');
        static::assertEquals('someAppId', $envelope->appId());


        static::assertNull($envelope->messageId());
        $envelope->setupMessageId('someMessageId');
        static::assertEquals('someMessageId', $envelope->messageId());


        static::assertEquals('body', $envelope->messageContent());
        static::assertEquals(['headerKey' => 'headerValue'], $envelope->headers());
    }
}
