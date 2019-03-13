<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\EntryPoint;

use ServiceBus\MessageSerializer\MessageDecoder;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use ServiceBus\Transport\Common\Transport;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Decoding of incoming messages.
 */
final class IncomingMessageDecoder
{
    public const DEFAULT_DECODER = 'service_bus.decoder.default_handler';

    /**
     * Decoders mapping.
     *
     * [
     *   'custom_encoder_key' => 'custom_decoder_id'
     * ]
     *
     * @var array<string, string>
     */
    private $decodersConfiguration;

    /**
     * @var ServiceLocator
     */
    private $decodersLocator;

    /**
     * @param array<string, string> $decodersConfiguration
     * @param ServiceLocator        $decodersLocator
     */
    public function __construct(array $decodersConfiguration, ServiceLocator $decodersLocator)
    {
        $this->decodersConfiguration = $decodersConfiguration;
        $this->decodersLocator       = $decodersLocator;
    }

    /**
     * Decodes a packet using a handler defined in the headers (or uses a default decoder).
     *
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param IncomingPackage $package
     *
     * @throws \LogicException Could not find decoder in the service container
     * @throws \ServiceBus\MessageSerializer\Exceptions\DecodeMessageFailed
     *
     * @return object
     */
    public function decode(IncomingPackage $package): object
    {
        $encoderKey = $this->extractEncoderKey($package->headers());

        return $this
            ->findDecoderByKey($encoderKey)
            ->decode($package->payload());
    }

    /**
     * @param string $encoderKey
     *
     * @throws \LogicException Could not find decoder
     *
     * @return MessageDecoder
     */
    private function findDecoderByKey(string $encoderKey): MessageDecoder
    {
        /** @var string $encoderContainerId */
        $encoderContainerId = true === !empty($this->decodersConfiguration[$encoderKey])
            ? $this->decodersConfiguration[$encoderKey]
            : self::DEFAULT_DECODER;

        return $this->obtainDecoder($encoderContainerId);
    }

    /**
     * @param string $decoderId
     *
     * @throws \LogicException Could not find decoder
     *
     * @return MessageDecoder
     */
    private function obtainDecoder(string $decoderId): MessageDecoder
    {
        if (true === $this->decodersLocator->has($decoderId))
        {
            /** @var MessageDecoder $decoder */
            $decoder = $this->decodersLocator->get($decoderId);

            return $decoder;
        }

        throw new \LogicException(
            \sprintf('Could not find decoder "%s" in the service container', $decoderId)
        );
    }

    /**
     * @param array $headers
     *
     * @return string
     */
    private function extractEncoderKey(array $headers): string
    {
        /** @var string $encoderKey */
        $encoderKey = $headers[Transport::SERVICE_BUS_SERIALIZER_HEADER] ?? self::DEFAULT_DECODER;

        return $encoderKey;
    }
}
