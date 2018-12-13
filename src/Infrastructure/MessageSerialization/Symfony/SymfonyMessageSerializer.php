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

namespace Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony;

use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\DecodeMessageFailed;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Exceptions\EncodeMessageFailed;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageDecoder;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\MessageEncoder;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\Extensions\EmptyDataDenormalizer;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\Extensions\EmptyDataNormalizer;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\Extensions\PropertyNameConverter;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\Extensions\PropertyNormalizerWrapper;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer;

/**
 *
 */
final class SymfonyMessageSerializer implements MessageEncoder, MessageDecoder
{
    private const JSON_ERRORS_MAPPING = [
        \JSON_ERROR_DEPTH                 => 'The maximum stack depth has been exceeded',
        \JSON_ERROR_STATE_MISMATCH        => 'Invalid or malformed JSON',
        \JSON_ERROR_CTRL_CHAR             => 'Control character error, possibly incorrectly encoded',
        \JSON_ERROR_SYNTAX                => 'Syntax error',
        \JSON_ERROR_UTF8                  => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        \JSON_ERROR_RECURSION             => 'One or more recursive references in the value to be encoded',
        \JSON_ERROR_INF_OR_NAN            => 'One or more NAN or INF values in the value to be encoded',
        \JSON_ERROR_UNSUPPORTED_TYPE      => 'A value of a type that cannot be encoded was given',
        \JSON_ERROR_INVALID_PROPERTY_NAME => 'A property name that cannot be encoded was given',
        \JSON_ERROR_UTF16                 => 'Malformed UTF-16 characters, possibly incorrectly encoded'
    ];

    /**
     * Symfony serializer
     *
     * @var Serializer\Serializer
     */
    private $serializer;

    public function __construct()
    {
        $this->serializer = new Serializer\Serializer([
                new Serializer\Normalizer\DateTimeNormalizer([
                    Serializer\Normalizer\DateTimeNormalizer::FORMAT_KEY => 'c'
                ],
                    new \DateTimeZone('UTC')
                ),
                new Serializer\Normalizer\ArrayDenormalizer(),
                new PropertyNormalizerWrapper(
                    null,
                    new PropertyNameConverter(),
                    new PhpDocExtractor()
                ),
                new EmptyDataDenormalizer(),
                new EmptyDataNormalizer()
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'service_bus.default_encoder';
    }

    /**
     * @inheritDoc
     */
    public function decode(string $serializedMessage): Message
    {
        try
        {
            $data = $this->jsonDecode($serializedMessage);

            self::validateUnserializedData($data);

            /** @var Message $object */
            $object = $this->denormalize($data['message'], $data['namespace']);

            return $object;
        }
        catch(\Throwable $throwable)
        {
            throw new DecodeMessageFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @inheritDoc
     */
    public function encode(Message $message): string
    {
        try
        {
            return $this->jsonEncode(\get_class($message), $this->normalize($message));
        }
        catch(\Throwable $throwable)
        {
            throw new EncodeMessageFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    /**
     * @inheritdoc
     */
    public function denormalize(array $payload, string $class): object
    {
        /** @var object $object */
        $object = $this->serializer->denormalize(
            $payload,
            $class
        );

        return $object;
    }

    /**
     * @inheritdoc
     */
    public function normalize(object $message): array
    {
        $data = $this->serializer->normalize($message);

        if(true === \is_array($data))
        {
            return $data;
        }

        // @codeCoverageIgnoreStart
        throw new \UnexpectedValueException(
            \sprintf(
                'The normalization was to return the array. Type "%s" was obtained when object "%s" was normalized',
                \gettype($data),
                \get_class($message)
            )
        );
        // @codeCoverageIgnoreEnd
    }

    /**
     * json encode
     *
     * @param string $messageClass
     * @param array  $payload
     *
     * @return string
     *
     * @throws \RuntimeException When json_encode failed
     */
    private function jsonEncode(string $messageClass, array $payload): string
    {
        /** Clear last error */
        \json_last_error();

        $encoded = \json_encode(['message' => $payload, 'namespace' => $messageClass]);

        self::throwExceptionIfJsonError();

        return $encoded;
    }

    /**
     * @param string $json
     *
     * @return array{message:array<string, mixed>, namespace:string}
     *
     * @throws \RuntimeException When json_decode failed
     */
    private function jsonDecode(string $json): array
    {
        /** Clear last error */
        \json_last_error();

        /** @var array{message:array<string, mixed>, namespace:string} $decoded */
        $decoded = \json_decode($json, true);

        self::throwExceptionIfJsonError();

        return $decoded;
    }

    /**
     * Verifying the correctness of the operation encode\decode
     *
     * @return void
     *
     * @throws \RuntimeException When json_encode\json_decode failed
     */
    private static function throwExceptionIfJsonError(): void
    {
        $lastResultCode = \json_last_error();

        if(\JSON_ERROR_NONE !== $lastResultCode)
        {
            throw new \RuntimeException(
                \sprintf(
                    'Error when working with JSON: %s',
                    self::JSON_ERRORS_MAPPING[$lastResultCode] ?? 'Unknown error'
                )
            );
        }
    }

    /**
     * @param array $data
     *
     * @return void
     *
     * @throws \UnexpectedValueException
     */
    private static function validateUnserializedData(array $data): void
    {
        /** Let's check if there are mandatory fields */
        if(
            false === isset($data['namespace']) ||
            false === isset($data['message'])
        )
        {
            throw new \UnexpectedValueException(
                'The serialized data must contains a "namespace" field (indicates the message class) and "message" (indicates the message parameters)'
            );
        }

        /** Let's check if the specified class exists */
        if('' === $data['namespace'] || false === \class_exists((string) $data['namespace']))
        {
            throw new \UnexpectedValueException(
                \sprintf('Class "%s" not found', $data['namespace'])
            );
        }
    }
}
