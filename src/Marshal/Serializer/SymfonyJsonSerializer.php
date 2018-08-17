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

namespace Desperado\ServiceBus\Marshal\Serializer;

use Desperado\ServiceBus\Marshal\Serializer\Exceptions\DeserializationFailed;
use Desperado\ServiceBus\Marshal\Serializer\Exceptions\SerializationFailed;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

/**
 * Symfony serializer adapter
 */
final class SymfonyJsonSerializer implements ArraySerializer
{
    /**
     * Symfony serializer
     *
     * @var SymfonySerializer
     */
    private $serializer;

    public function __construct()
    {
        $this->serializer = new SymfonySerializer([], [new JsonEncoder()]);
    }

    /**
     * @inheritdoc
     */
    public function serialize(array $data): string
    {
        try
        {
            $serialized = $this->serializer->encode($data, 'json');

            if(true === \is_string($serialized))
            {
                return $serialized;
            }

            // @codeCoverageIgnoreStart
            throw new \UnexpectedValueException(
                \sprintf(
                    'An incorrect type of data received during the serialization process: "%s". string expected',
                    \gettype($serialized)
                )
            );
            // @codeCoverageIgnoreEnd
        }
        catch(\Throwable $throwable)
        {
            throw new SerializationFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function unserialize(string $payload): array
    {
        try
        {
            $data = $this->serializer->decode($payload, 'json');

            if(true === \is_array($data))
            {
                return $data;
            }

            // @codeCoverageIgnoreStart
            throw new \UnexpectedValueException(
                \sprintf(
                    'An incorrect type of data received in the process of deserialization: "%s". array expected',
                    \gettype($data)
                )
            );
            // @codeCoverageIgnoreEnd
        }
        catch(\Throwable $throwable)
        {
            $message = \sprintf('Incorrect json format: %s', $throwable->getMessage());

            throw new DeserializationFailed($message, $throwable->getCode(), $throwable);
        }
    }
}
