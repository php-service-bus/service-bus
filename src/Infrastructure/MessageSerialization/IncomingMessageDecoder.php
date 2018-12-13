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

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Desperado\ServiceBus\Infrastructure\Transport\Transport;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Decoding of incoming messages
 */
final class IncomingMessageDecoder
{
    public const DEFAULT_DECODER = 'service_bus.default_encoder';

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
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param IncomingPackage $package
     *
     * @return Message
     *
     * @throws \LogicException Could not find encoder in the service container
     * @throws \Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed
     */
    public function decode(IncomingPackage $package): Message
    {
        $encoderKey = self::extractEncoderKey($package->headers());

        if(false === $this->decodersContainer->has($encoderKey))
        {
            throw new \LogicException(
                \sprintf('Could not find encoder "%s" in the service container', $encoderKey)
            );
        }

        /** @var MessageDecoder $encoder */
        $encoder = $this->decodersContainer->get($encoderKey);

        /** @var Message $message */
        $message = $encoder->decode($package->payload());

        return $message;
    }

    /**
     * @param array $headers
     *
     * @return string
     */
    private static function extractEncoderKey(array $headers): string
    {
        /** @var string $encoderKey */
        $encoderKey = $headers[Transport::SERVICE_BUS_SERIALIZER_HEADER] ?? self::DEFAULT_DECODER;

        return $encoderKey;
    }
}
