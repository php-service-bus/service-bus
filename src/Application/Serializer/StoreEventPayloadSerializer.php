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

namespace Desperado\Framework\Application\Serializer;

use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Domain\Messages\ReceivedMessage;
use Desperado\Framework\Domain\ParameterBag;
use Desperado\Framework\Domain\Serializer\MessageSerializerInterface;

/**
 * A serializer for storing events in the database
 */
class StoreEventPayloadSerializer implements MessageSerializerInterface
{
    private const COMPRESS_LEVEL = 7;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * @param MessageSerializerInterface $messageSerializer
     */
    public function __construct(MessageSerializerInterface $messageSerializer)
    {
        $this->messageSerializer = $messageSerializer;
    }


    /**
     * @inheritdoc
     */
    public function serialize(MessageInterface $message, ParameterBag $metadata = null): string
    {
        $serializedContent = $this->messageSerializer->serialize($message, $metadata);

        return \base64_encode(\gzcompress($serializedContent, self::COMPRESS_LEVEL));
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $content): ReceivedMessage
    {
        $content = \gzuncompress(\base64_decode($content));

        return $this->messageSerializer->unserialize($content);
    }
}
