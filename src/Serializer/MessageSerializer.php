<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Serializer;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageSerializer\Exceptions\MessageSerializationFailException;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Domain\Serializer\SerializedObject;
use Desperado\Domain\Serializer\SerializerInterface;

/**
 * Message serializer
 */
class MessageSerializer implements MessageSerializerInterface
{
    private const FORMAT_JSON = 'json';

    /**
     * Serializer
     *
     * @var SerializerInterface
     */
    private $serializeHandler;

    /**
     * @param SerializerInterface $serializeHandler
     */
    public function __construct(SerializerInterface $serializeHandler)
    {
        $this->serializeHandler = $serializeHandler;
    }

    /**
     * @inheritdoc
     */
    public function serialize(AbstractMessage $message): string
    {
        try
        {
            $serializedRepresentation = new SerializedObject(
                $this->serializeHandler->normalize($message),
                \get_class($message)
            );

            $normalized = $this->serializeHandler->normalize($serializedRepresentation);

            return $this->serializeHandler->encode(
                $normalized,
                SerializedObject::class,
                self::FORMAT_JSON
            );
        }
        catch(\Throwable $throwable)
        {
            throw new MessageSerializationFailException(
                \sprintf('Serialize message fail: %s', $throwable->getMessage()),
                $throwable->getCode(),
                $throwable
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $content): AbstractMessage
    {
        try
        {
            $normalizedPayload = $this->serializeHandler->decode($content, self::FORMAT_JSON);

            /** @var SerializedObject $serializedMessage */
            $serializedMessage = $this->serializeHandler->denormalize(
                $normalizedPayload, SerializedObject::class
            );

            /** @var AbstractMessage $message */
            $message = $this->serializeHandler->denormalize(
                $serializedMessage->getMessage(), $serializedMessage->getNamespace()
            );

            return $message;
        }
        catch(\Throwable $throwable)
        {

            throw new MessageSerializationFailException(
                \sprintf('Unserialize message fail: %s', $throwable->getMessage()),
                $throwable->getCode(),
                $throwable
            );
        }
    }
}
