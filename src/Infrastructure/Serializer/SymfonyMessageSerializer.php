<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\Serializer;

use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\ReceivedMessage;
use Desperado\ConcurrencyFramework\Domain\Messages\SerializedMessage;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Symfony\Component\PropertyInfo;
use Symfony\Component\Serializer;
use Desperado\ConcurrencyFramework\Infrastructure\Serializer\Exceptions;

/**
 * Symfony message serializer
 */
class SymfonyMessageSerializer implements MessageSerializerInterface
{
    public const JSON_SERIALIZATION = 'json';

    private const AVAILABLE_FORMATS = [
        self::JSON_SERIALIZATION
    ];

    /**
     * Serialization format
     *
     * @var string
     */
    private $format;

    /**
     * Symfony serializer
     *
     * @var Serializer\Serializer
     */
    private $serializer;

    /**
     * @param string $format
     */
    public function __construct(string $format = self::JSON_SERIALIZATION)
    {
        $this->format = self::guardFormat($format);
    }

    /**
     * @inheritdoc
     */
    public function serialize(MessageInterface $content): string
    {
        try
        {
            $normalizedPayload = $this
                ->getSerializer()
                ->normalize($content);

            $message = SerializedMessage::create($content, $normalizedPayload);

            return (string) $this
                ->getSerializer()
                ->encode(
                    $message->toArray(),
                    $this->format
                );
        }
        catch(Serializer\Exception\ExceptionInterface $exception)
        {
            throw new Exceptions\MessageSerializationFailException(
                'Serialize message fail', $exception->getCode(), $exception
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
            $normalizedPayload = $this
                ->getSerializer()
                ->decode(
                    $content, $this->format
                );

            $serializedMessage = SerializedMessage::restore($normalizedPayload);

            /** @var MessageInterface $message */
            $message = $this
                ->getSerializer()
                ->denormalize(
                    $serializedMessage->getPayload(),
                    $serializedMessage->getMessageClassNamespace()
                );

            return new ReceivedMessage($message, $serializedMessage->getMetadata());
        }
        catch(Serializer\Exception\ExceptionInterface $exception)
        {
            throw new Exceptions\MessageSerializationFailException(
                'Unserialize message fail', $exception->getCode(), $exception
            );
        }
    }

    /**
     * Get serializer instance
     *
     * @return Serializer\Serializer
     */
    protected function getSerializer(): Serializer\Serializer
    {
        if(null === $this->serializer)
        {
            $this->serializer = new Serializer\Serializer(
                self::getNormalizers(),
                self::getEncoders()
            );
        }

        return $this->serializer;
    }

    /**
     * Get encoders collection
     *
     * @return array
     */
    private static function getEncoders(): array
    {
        return [
            new Serializer\Encoder\JsonEncoder()
        ];
    }

    /**
     * Get normalizers collection
     *
     * @return Serializer\Normalizer\NormalizerInterface[]
     */
    private static function getNormalizers(): array
    {
        return [
            new Serializer\Normalizer\DateTimeNormalizer(),
            new Serializer\Normalizer\ArrayDenormalizer(),
            new Serializer\Normalizer\ObjectNormalizer(
                null,
                null,
                null,
                new PropertyInfo\Extractor\PhpDocExtractor()
            )
        ];
    }

    /**
     * Assert serialization format is valid
     *
     * @param string $format
     *
     * @return string
     *
     * @throws Exceptions\UnexpectedSerializationFormatException
     */
    private static function guardFormat(string $format): string
    {
        $format = \strtolower($format);

        if('' === $format)
        {
            throw new Exceptions\UnexpectedSerializationFormatException('Serialization format must be specified');
        }

        if(false === \in_array($format, self::AVAILABLE_FORMATS, true))
        {
            throw new Exceptions\UnexpectedSerializationFormatException(
                \sprintf(
                    'Unsupported serialization format specified ("%s"). Available choices: %s',
                    $format, \implode(', ', \array_values(self::AVAILABLE_FORMATS))
                )
            );
        }

        return $format;
    }
}
