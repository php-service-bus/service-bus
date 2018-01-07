<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Serializer;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Domain\Tests\TestFixtures\SomeEvent;
use PHPUnit\Framework\TestCase;

/**
 * Base test case
 */
abstract class AbstractSerializerTestCase extends TestCase
{

    /**
     * Get message serializer instance
     *
     * @return MessageSerializerInterface
     */
    abstract protected function getMessageSerializer(): MessageSerializerInterface;

    /**
     * @test
     *
     * @return void
     */
    public function serialize(): void
    {
        $serializedContent = $this->getMessageSerializer()->serialize(self::createMessage());

        if($this instanceof CompressMessageSerializerTest)
        {
            $content = \gzuncompress(\base64_decode($serializedContent));
        }
        else
        {
            $content = $this->getMessageSerializer()->serialize(self::createMessage());
        }

        $decodedJson = \json_decode($content, true);

        static::assertArrayHasKey('message', $decodedJson);
        static::assertArrayHasKey('namespace', $decodedJson);

        static::assertArrayHasKey('someEventId', $decodedJson['message']);
        static::assertArrayHasKey('someEventValue', $decodedJson['message']);

        static::assertSame(SomeEvent::class, $decodedJson['namespace']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function unserialize(): void
    {
        $encodedMessage = $this->getMessageSerializer()->serialize(self::createMessage());
        $decodedMessage = $this->getMessageSerializer()->unserialize($encodedMessage);

        static::assertInstanceOf(AbstractMessage::class, $decodedMessage);
        static::assertInstanceOf(SomeEvent::class, $decodedMessage);
    }

    /**
     * @test
     * @expectedException \Desperado\Domain\MessageSerializer\Exceptions\MessageSerializationFailException
     * @expectedExceptionMessage Unserialize message fail: Object denormalize fail: The type of the "someEventId"
     *                           attribute for class "Desperado\Domain\Tests\TestFixtures\SomeEvent" must be one of
     *                           "string" ("double" given).
     *
     * @return void
     */
    public function failedUnserialize(): void
    {
        $message = SomeEvent::create(['someEventId' => 10.15]);

        $this->getMessageSerializer()->unserialize(
            $this->getMessageSerializer()->serialize($message)
        );
    }

    /**
     * Create test event
     *
     * @return SomeEvent
     */
    private static function createMessage(): SomeEvent
    {
        $event = SomeEvent::create([
            'someEventId' => \sha1(\random_bytes(36)),
            'someEventValue' => \sha1(\random_bytes(128))
        ]);

        return $event;
    }
}
