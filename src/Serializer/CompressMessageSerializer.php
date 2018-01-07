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
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;

/**
 * A serializer with gzip compress support
 */
class CompressMessageSerializer implements MessageSerializerInterface
{
    private const DEFAULT_COMPRESS_LEVEL = 7;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * The level of compression. Can be given as 0 for no compression up to 9 for maximum compression
     *
     * @var int
     */
    private $compressionLevel;

    /**
     * @param MessageSerializerInterface $messageSerializer
     * @param int                        $compressionLevel
     */
    public function __construct(
        MessageSerializerInterface $messageSerializer,
        $compressionLevel = self::DEFAULT_COMPRESS_LEVEL
    )
    {
        $this->messageSerializer = $messageSerializer;
        $this->compressionLevel = $compressionLevel;
    }

    /**
     * @inheritdoc
     */
    public function serialize(AbstractMessage $message): string
    {
        $serializedContent = $this->messageSerializer->serialize($message);

        return \base64_encode(
            \gzcompress(
                $serializedContent,
                $this->compressionLevel
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $content): AbstractMessage
    {
        $content = \gzuncompress(
            \base64_decode($content)
        );

        return $this->messageSerializer->unserialize($content);
    }
}
