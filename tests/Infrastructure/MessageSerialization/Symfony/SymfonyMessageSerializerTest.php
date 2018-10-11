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

namespace Desperado\ServiceBus\Tests\Infrastructure\MessageSerialization\Symfony;

use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\SymfonyMessageSerializer;
use Desperado\ServiceBus\Tests\Stubs\Messages\CommandWithPayload;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class SymfonyMessageSerializerTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function successEncode(): void
    {
        $encoder = new SymfonyMessageSerializer();

        $originMessage = new CommandWithPayload('qwerty');

        $result = $encoder->decode($encoder->encode($originMessage));

        static::assertEquals($originMessage, $result);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\EncodeMessageFailed
     * @expectedExceptionMessage Error when working with JSON: Malformed UTF-8 characters, possibly incorrectly encoded
     *
     * @return void
     */
    public function failEncode(): void
    {
        $message = new CommandWithPayload(\iconv('utf-8', 'windows-1251', 'контент'));

        (new SymfonyMessageSerializer())->encode($message);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed
     * @expectedExceptionMessage Class "SomeClass" not found
     *
     * @return void
     */
    public function classNotFound(): void
    {
        (new SymfonyMessageSerializer())->decode(\json_encode(['message' => 'someValue', 'namespace' => \SomeClass::class]));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed
     * @expectedExceptionMessage The serialized data must contains a "namespace" field (indicates the message class)
     *                           and "message" (indicates the message parameters)
     *
     * @return void
     */
    public function withoutNamespace(): void
    {
        (new SymfonyMessageSerializer())->decode(\json_encode(['message' => 'someValue']));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed
     * @expectedExceptionMessage The serialized data must contains a "namespace" field (indicates the message class)
     *                           and "message" (indicates the message parameters)
     *
     * @return void
     */
    public function withoutPayload(): void
    {
        (new SymfonyMessageSerializer())->decode(\json_encode(['namespace' => __CLASS__]));
    }
}
