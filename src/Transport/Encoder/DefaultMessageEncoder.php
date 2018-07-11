<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\Encoder;

use Desperado\Contracts\Common\Message;
use Desperado\ServiceBus\Transport\Exceptions\EncodeMessageFailed;
use Desperado\ServiceBus\Transport\MessageDTO;
use Desperado\ServiceBus\Transport\Normalizer\Normalizer;
use Desperado\ServiceBus\Transport\Serializer\Serializer;

/**
 * Default encoder
 */
final class DefaultMessageEncoder implements MessageEncoder
{
    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param Normalizer $normalizer
     * @param Serializer $serializer
     */
    public function __construct(Normalizer $normalizer, Serializer $serializer)
    {
        $this->normalizer = $normalizer;
        $this->serializer = $serializer;
    }

    /**
     * @inheritdoc
     */
    public function encode(Message $message): string
    {
        try
        {
            $serializedMessage = new MessageDTO(
                $this->normalizer->normalize($message),
                \get_class($message)
            );

            return $this->serializer->serialize(
                $this->normalizer->normalize($serializedMessage)
            );
        }
        catch(\Throwable $throwable)
        {
            throw new EncodeMessageFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }
}
