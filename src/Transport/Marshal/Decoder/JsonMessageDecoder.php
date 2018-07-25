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

namespace Desperado\ServiceBus\Transport\Marshal\Decoder;

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Marshal\Denormalizer\Denormalizer;
use Desperado\ServiceBus\Marshal\Serializer\ArraySerializer;
use Desperado\ServiceBus\Transport\Marshal\Exceptions\DecodeMessageFailed;
use Desperado\ServiceBus\Transport\Marshal\MessageDTO;

/**
 * Restore the message object
 */
final class JsonMessageDecoder implements TransportMessageDecoder
{
    /**
     * @var Denormalizer
     */
    private $denormalizer;

    /**
     * @var ArraySerializer
     */
    private $serializer;

    /**
     * @param Denormalizer    $denormalizer
     * @param ArraySerializer $serializer
     */
    public function __construct(Denormalizer $denormalizer, ArraySerializer $serializer)
    {
        $this->denormalizer = $denormalizer;
        $this->serializer   = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function decode(string $serializedMessage): Message
    {
        $unserialized = $this->unserialize($serializedMessage);

        return $this->denormalize(
            $unserialized['namespace'],
            $unserialized['message']
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $serializedMessage): array
    {
        try
        {
            /** @see MessageDTO */

            $unserialized = $this->serializer->unserialize($serializedMessage);

            $this->validateUnserializedData($unserialized);

            return $unserialized;
        }
        catch(\Throwable $throwable)
        {
            throw new DecodeMessageFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     *
     * @inheritdoc
     */
    public function denormalize(string $messageClass, array $payload): Message
    {
        try
        {
            /** @noinspection PhpIncompatibleReturnTypeInspection */
            return $this->denormalizer->denormalize(
                $messageClass,
                $payload
            );
        }
        catch(\Throwable $throwable)
        {
            throw new DecodeMessageFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws \UnexpectedValueException
     */
    private function validateUnserializedData(array $data): void
    {
        /** Let's check if there are mandatory fields */
        if(false === isset($data['namespace']) || false === isset($data['message']))
        {
            throw new \UnexpectedValueException(
                \sprintf(
                    \sprintf(
                        'The serialized data must contains a "namespace" field (indicates the message class) and '
                        . '"message" (indicates the message parameters). This is a serialized representation of the object "%s"',
                        MessageDTO::class
                    )
                )
            );
        }

        /** Let's check if the specified class exists */
        if('' === $data['namespace'] || false === \class_exists($data['namespace']))
        {
            throw new \UnexpectedValueException(
                \sprintf('Class "%s" not found', $data['namespace'])
            );
        }
    }
}
