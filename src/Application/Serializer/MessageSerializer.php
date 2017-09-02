<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Application\Serializer;

use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\ReceivedMessage;
use Desperado\ConcurrencyFramework\Domain\Messages\SerializedMessage;
use Desperado\ConcurrencyFramework\Domain\ParameterBag;
use Desperado\ConcurrencyFramework\Domain\Serializer\Exceptions\MessageSerializationFailException;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Domain\Serializer\SerializerInterface;

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
    public function serialize(MessageInterface $message, ParameterBag $metadata = null): string
    {
        $metadata = $metadata ?? new ParameterBag();

        try
        {
            $serializedRepresentation = new SerializedMessage();
            $serializedRepresentation->message = $this->serializeHandler->normalize($message);
            $serializedRepresentation->namespace = \get_class($message);
            $serializedRepresentation->metadata = $metadata->all();

            $normalized = $this->serializeHandler->normalize($serializedRepresentation);

            return $this->serializeHandler->encode(
                $normalized,
                SerializedMessage::class
                , self::FORMAT_JSON
            );
        }
        catch(\Throwable $throwable)
        {
            throw new MessageSerializationFailException(
                'Serialize message fail', $throwable->getCode(), $throwable
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $content): ReceivedMessage
    {
        try
        {
            $normalizedPayload = $this->serializeHandler->decode($content, self::FORMAT_JSON);

            /** @var SerializedMessage $serializedMessage */
            $serializedMessage = $this->serializeHandler->denormalize(
                $normalizedPayload, SerializedMessage::class
            );

            $receivedMessage = new ReceivedMessage();

            $receivedMessage->metadata = new ParameterBag($serializedMessage->metadata);
            $receivedMessage->message = $this->serializeHandler->denormalize(
                $serializedMessage->message, $serializedMessage->namespace
            );

            return $receivedMessage;
        }
        catch(\Throwable $throwable)
        {
            throw new MessageSerializationFailException(
                'Unserialize message fail', $throwable->getCode(), $throwable
            );
        }
    }
}
