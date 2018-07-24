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

            throw new \UnexpectedValueException(
                \sprintf(
                    'An incorrect type of data received during the serialization process: "%s". string expected',
                    \gettype($serialized)
                )
            );
        }
        catch(\Throwable $throwable)
        {
            throw new \RuntimeException($throwable->getMessage(), $throwable->getCode(), $throwable);
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

            throw new \UnexpectedValueException(
                \sprintf(
                    'An incorrect type of data received in the process of deserialization: "%s". array expected',
                    \gettype($data)
                )
            );
        }
        catch(\Throwable $throwable)
        {
            $message = \sprintf('Incorrect json format: %s', $throwable->getMessage());

            throw new \RuntimeException($message, $throwable->getCode(), $throwable);
        }
    }
}
