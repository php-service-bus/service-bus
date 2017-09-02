<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Tests\Application\Serializer;

use Desperado\ConcurrencyFramework\Application\Serializer\MessageSerializer;
use Desperado\ConcurrencyFramework\Domain\Messages\ReceivedMessage;
use Desperado\ConcurrencyFramework\Domain\Serializer\SerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Serializer\SymfonySerializer;
use Desperado\ConcurrencyFramework\Tests\TestFixtures\Events\SomeEvent;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SerializerTest extends TestCase
{
    /**
     * Serializer
     *
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * Message serializer
     *
     * @var MessageSerializer
     */
    private $messageSerializer;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->serializer = new SymfonySerializer();
        $this->messageSerializer = new MessageSerializer($this->serializer);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown()
    {
        parent::tearDown();

        unset($this->serializer, $this->messageSerializer);
    }

    /**
     * @test
     *
     * @return void
     */
    public function serialize(): void
    {
        $decodedJson = \json_decode(
            $this->messageSerializer->serialize(self::createMessage()),
            true
        );

        static::assertArrayHasKey('message', $decodedJson);
        static::assertArrayHasKey('namespace', $decodedJson);
        static::assertArrayHasKey('metadata', $decodedJson);

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
        $encodedMessage = $this->messageSerializer->serialize(self::createMessage());
        $decodedMessage = $this->messageSerializer->unserialize($encodedMessage);

        static::assertInstanceOf(ReceivedMessage::class, $decodedMessage);
        static::assertInstanceOf(SomeEvent::class, $decodedMessage->message);
    }

    /**
     * Create test event
     *
     * @return SomeEvent
     */
    private static function createMessage(): SomeEvent
    {
        $event = new SomeEvent();
        $event->someEventId = \sha1(\random_bytes(36));
        $event->someEventValue = \sha1(\random_bytes(128));

        return $event;
    }
}
