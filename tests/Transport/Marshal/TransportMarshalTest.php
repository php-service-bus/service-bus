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

namespace Desperado\ServiceBus\Tests\Transport\Marshal;

use Desperado\ServiceBus\Tests\Stubs\Messages\CommandWithPayload;
use Desperado\ServiceBus\Transport\Marshal\Decoder\JsonMessageDecoder;
use Desperado\ServiceBus\Transport\Marshal\Encoder\JsonMessageEncoder;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class TransportMarshalTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function flow(): void
    {
        $message = new CommandWithPayload('qwerty');

        $encoded = (new JsonMessageEncoder())->encode($message);
        $array   = \json_decode($encoded, true);

        static::assertNotFalse($array);
        static::assertArrayHasKey('message', $array);
        static::assertArrayHasKey('namespace', $array);
        static::assertArrayHasKey('payload', $array['message']);
        static::assertEquals(CommandWithPayload::class, $array['namespace']);
        static::assertEquals('qwerty', $array['message']['payload']);

        static::assertEquals(
            $message,
            (new JsonMessageDecoder())->decode($encoded)
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Transport\Marshal\Exceptions\EncodeMessageFailed
     *
     * @return void
     */
    public function failEncode(): void
    {
        $message = new CommandWithPayload(\iconv('utf-8', 'windows-1251', 'контент'));

        (new JsonMessageEncoder())->encode($message);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Transport\Marshal\Exceptions\DecodeMessageFailed
     *
     * @return void
     */
    public function failDecode(): void
    {
        (new JsonMessageDecoder())->decode('qwerty');
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Transport\Marshal\Exceptions\DecodeMessageFailed
     * @expectedExceptionMessage Class 'SomeClass' not found
     *
     * @return void
     */
    public function failedDenormalize(): void
    {
        (new JsonMessageDecoder())->denormalize(\SomeClass::class, []);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Transport\Marshal\Exceptions\DecodeMessageFailed
     *
     * @return void
     */
    public function unserializeWithoutExpectedKeys(): void
    {
        (new JsonMessageDecoder())->unserialize(\json_encode(['someKey' => 'someValue']));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Transport\Marshal\Exceptions\DecodeMessageFailed
     * @expectedExceptionMessage Class "SomeClass" not found
     *
     * @return void
     */
    public function unserializeWithWrongNamespace(): void
    {
        (new JsonMessageDecoder())->unserialize(\json_encode(['message' => 'someValue', 'namespace' => \SomeClass::class]));
    }
}
