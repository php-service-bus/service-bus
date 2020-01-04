<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\EntryPoint;

use PHPUnit\Framework\TestCase;
use ServiceBus\EntryPoint\Exceptions\UnexpectedMessageDecoder;
use ServiceBus\EntryPoint\IncomingMessageDecoder;
use ServiceBus\MessageSerializer\MessageDecoder;
use ServiceBus\MessageSerializer\Symfony\SymfonyMessageSerializer;
use ServiceBus\Transport\Common\Transport;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ServiceLocator;
use function ServiceBus\Common\jsonEncode;

/**
 *
 */
final class IncomingMessageDecoderTest extends TestCase
{
    /** @var IncomingMessageDecoder */
    private $decoder;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->set('default_serializer', new SymfonyMessageSerializer());
        $containerBuilder->set(
            'custom_serializer',
            new class() implements MessageDecoder
            {
                public function decode(string $serializedMessage): object
                {
                    return \unserialize($serializedMessage);
                }

                public function denormalize(array $payload, string $class): object
                {
                    /** not used here */
                    return new \stdClass();
                }
            }
        );

        $this->decoder = new IncomingMessageDecoder(
            [
                'service_bus.decoder.default_handler' => 'default_serializer',
                'service_bus.decoder.custom_handler'  => 'custom_serializer',
                'non_exists_handler'                  => 'some_service_id'
            ],
            $containerBuilder
        );
    }

    /** @test */
    public function decodeWithoutHeader(): void
    {
        $message = new EntryPointTestMessage('qwerty');
        $package = new EntryPointTestIncomingPackage(self::serializeToDefaultHandler($message));

        /** @var EntryPointTestMessage $decodedMessage */
        $decodedMessage = $this->decoder->decode($package);

        static::assertSame('qwerty', $decodedMessage->id);
    }

    /** @test */
    public function decodeWithDefaultHandler(): void
    {
        $message = new EntryPointTestMessage('qwerty');
        $package = new EntryPointTestIncomingPackage(self::serializeToDefaultHandler($message), [
            Transport::SERVICE_BUS_SERIALIZER_HEADER =>  'service_bus.decoder.default_handler'
        ]);

        /** @var EntryPointTestMessage $decodedMessage */
        $decodedMessage = $this->decoder->decode($package);

        static::assertSame('qwerty', $decodedMessage->id);
    }

    /** @test */
    public function decodeWithCustomHandler(): void
    {
        $message = new EntryPointTestMessage('qwerty');
        $package = new EntryPointTestIncomingPackage(\serialize($message), [
            Transport::SERVICE_BUS_SERIALIZER_HEADER =>  'service_bus.decoder.custom_handler'
        ]);

        /** @var EntryPointTestMessage $decodedMessage */
        $decodedMessage = $this->decoder->decode($package);

        static::assertSame('qwerty', $decodedMessage->id);
    }

    /** @test */
    public function withUnknownHandler(): void
    {
        $this->expectException(UnexpectedMessageDecoder::class);
        $this->expectExceptionMessage('Could not find decoder "some_service_id" in the service container');

        $message = new EntryPointTestMessage('qwerty');
        $package = new EntryPointTestIncomingPackage(
            self::serializeToDefaultHandler($message),
            [
                Transport::SERVICE_BUS_SERIALIZER_HEADER => 'non_exists_handler'
            ]
        );

        $this->decoder->decode($package);
    }

    private static function serializeToDefaultHandler(object $message): string
    {
        return jsonEncode([
            'namespace' => \get_class($message),
            'message'   => $message->jsonSerialize()
        ]);
    }
}
