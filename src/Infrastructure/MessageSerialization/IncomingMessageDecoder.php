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
     * @return Promise<\Desperado\ServiceBus\Common\Contract\Messages\Message>
     *
     * @throws \LogicException Could not find encoder in the service container
     * @throws \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed Error while
     *                                                                                                  decoding
     *                                                                                                  message
     */
    public function decode(IncomingPackage $package): Promise
    {
        $decodersContainer = $this->decodersContainer;
        $deferred          = new Deferred();

        $watcherId = Loop::defer(
            static function() use ($package, $decodersContainer, $deferred, &$watcherId): \Generator
            {
                try
                {
                    $encoderKey = self::extractEncoderKey($package->headers());

                    if(true === $decodersContainer->has($encoderKey))
                    {
                        /** @var MessageDecoder $encoder */
                        $encoder = $decodersContainer->get($encoderKey);

                        $deferred->resolve(
                            $encoder->decode(
                                yield $package->payload()->read()
                            )
                        );

                        unset($encoder, $result);

                        return;
                    }

                    throw new \LogicException(
                        \sprintf('Could not find encoder "%s" in the service container', $encoderKey)
                    );
                }
                catch(\Throwable $throwable)
                {
                    $deferred->fail($throwable);
                }
                finally
                {
                    Loop::cancel($watcherId);
                }
            }
        );

        Loop::unreference($watcherId);

        return $deferred->promise();
    }

    /**
     * @param array $headers
     *
     * @return string
     */
    private static function extractEncoderKey(array $headers): string
    {
        return $headers[self::HEADERS_ENCODER_KEY] ?? self::DEFAULT_ENCODER;
    }
}
