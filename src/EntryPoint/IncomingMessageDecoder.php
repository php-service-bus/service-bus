<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\EntryPoint;

use Psr\Container\ContainerInterface;
use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Common\Metadata\ServiceBusMetadata;
use ServiceBus\EntryPoint\Exceptions\UnexpectedMessageDecoder;
use ServiceBus\MessageSerializer\Exceptions\DecodeObjectFailed;
use ServiceBus\MessageSerializer\ObjectSerializer;

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
     * @psalm-var array<non-empty-string, non-empty-string>
     *
     * @var string[]
     */
    private $decodersConfiguration;

    /**
     * @var ContainerInterface
     */
    private $decodersLocator;

    /**
     * @psalm-param array<non-empty-string, non-empty-string> $decodersConfiguration
     */
    public function __construct(array $decodersConfiguration, ContainerInterface $decodersLocator)
    {
        $this->decodersConfiguration = $decodersConfiguration;
        $this->decodersLocator       = $decodersLocator;
    }

    /**
     * @psalm-param non-empty-string $payload
     *
     * @throws \ServiceBus\MessageSerializer\Exceptions\DecodeObjectFailed
     */
    public function decode(string $payload, IncomingMessageMetadata $metadata): object
    {
        $encoderKey = (string) $metadata->get(
            key: ServiceBusMetadata::SERVICE_BUS_SERIALIZER_TYPE,
            default: self::DEFAULT_DECODER
        );

        $encoderKey = $encoderKey !== '' ? $encoderKey : self::DEFAULT_DECODER;

        /** @psalm-var class-string|null $toMessageClass */
        $toMessageClass = $metadata->get(ServiceBusMetadata::SERVICE_BUS_MESSAGE_TYPE);

        if ($toMessageClass === null)
        {
            throw new DecodeObjectFailed('Unable to find message classFQN declaration');
        }

        return $this
            ->findDecoderByKey($encoderKey)
            ->decode(
                serializedObject: $payload,
                objectClass: $toMessageClass
            );
    }

    /**
     * @psalm-param non-empty-string $encoderKey
     *
     * @throws \ServiceBus\EntryPoint\Exceptions\UnexpectedMessageDecoder Could not find decoder
     */
    private function findDecoderByKey(string $encoderKey): ObjectSerializer
    {
        return $this->obtainDecoder(
            $this->decodersConfiguration[$encoderKey] ?? self::DEFAULT_DECODER
        );
    }

    /**
     * @psalm-param non-empty-string $decoderId
     *
     * @throws \ServiceBus\EntryPoint\Exceptions\UnexpectedMessageDecoder Could not find decoder
     */
    private function obtainDecoder(string $decoderId): ObjectSerializer
    {
        if ($this->decodersLocator->has($decoderId))
        {
            /**
             * @noinspection PhpUnhandledExceptionInspection
             * @noinspection PhpUnnecessaryLocalVariableInspection
             *
             * @var ObjectSerializer $decoder
             */
            $decoder = $this->decodersLocator->get($decoderId);

            return $decoder;
        }

        throw new UnexpectedMessageDecoder(
            \sprintf('Could not find decoder "%s" in the service container', $decoderId)
        );
    }
}
