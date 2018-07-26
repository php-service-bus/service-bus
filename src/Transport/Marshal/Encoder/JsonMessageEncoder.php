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

namespace Desperado\ServiceBus\Transport\Marshal\Encoder;

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Marshal\Normalizer\Normalizer;
use Desperado\ServiceBus\Marshal\Normalizer\SymfonyPropertyNormalizer;
use Desperado\ServiceBus\Marshal\Serializer\ArraySerializer;
use Desperado\ServiceBus\Marshal\Serializer\SymfonyJsonSerializer;
use Desperado\ServiceBus\Transport\Marshal\Exceptions\EncodeMessageFailed;
use Desperado\ServiceBus\Transport\Marshal\MessageDTO;

/**
 * Default encoder
 */
final class JsonMessageEncoder implements TransportMessageEncoder
{
    /**
     * @var Normalizer
     */
    private $normalizer;

    /**
     * @var ArraySerializer
     */
    private $serializer;

    /**
     * @param Normalizer|null      $normalizer
     * @param ArraySerializer|null $serializer
     */
    public function __construct(Normalizer $normalizer = null, ArraySerializer $serializer = null)
    {
        $this->normalizer = $normalizer ?? new SymfonyPropertyNormalizer();
        $this->serializer = $serializer ?? new SymfonyJsonSerializer();
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
