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

namespace Desperado\ServiceBus\Infrastructure\MessageSerialization;

use Amp\Deferred;
use Amp\Loop;
use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Decoding of incoming messages
 */
final class IncomingMessageDecoder
{
    public const HEADERS_ENCODER_KEY = 'message.encoder';

    public const DEFAULT_ENCODER = 'service_bus.default_encoder';

    /**
     * @var ServiceLocator
     */
    private $decodersContainer;

    /**
     * @param ServiceLocator $decodersContainer
     */
    public function __construct(ServiceLocator $decodersContainer)
    {
        $this->decodersContainer = $decodersContainer;
    }

    /**
     * @param IncomingPackage $package
     *
     * @psalm-suppress MixedTypeCoercion
     *
     * @return Promise<\Desperado\ServiceBus\Common\Contract\Messages\Message>
     *
     * @throws \LogicException Could not find encoder in the service container
     * @throws \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed
     */
    public function decode(IncomingPackage $package): Promise
    {
        $decodersContainer = $this->decodersContainer;
        $deferred          = new Deferred();

        /** @psalm-suppress InvalidReturnType */
        Loop::defer(
            static function() use ($package, $decodersContainer, $deferred): \Generator
            {
                try
                {
                    $encoderKey = self::extractEncoderKey($package->headers());

                    if(false === $decodersContainer->has($encoderKey))
                    {
                        $deferred->fail(
                            new \LogicException(
                                \sprintf('Could not find encoder "%s" in the service container', $encoderKey)
                            )
                        );

                        return;
                    }

                    /** @var MessageDecoder $encoder */
                    $encoder = $decodersContainer->get($encoderKey);

                    /** @var string $payload */
                    $payload = yield $package->payload()->read();

                    $deferred->resolve(
                        $encoder->decode($payload)
                    );

                    unset($encoder, $payload);
                }
                catch(\Throwable $throwable)
                {
                    $deferred->fail($throwable);
                }
            }
        );

        return $deferred->promise();
    }

    /**
     * @param array $headers
     *
     * @return string
     */
    private static function extractEncoderKey(array $headers): string
    {
        /** @var string $encoderKey */
        $encoderKey = $headers[self::HEADERS_ENCODER_KEY] ?? self::DEFAULT_ENCODER;

        return $encoderKey;
    }
}
