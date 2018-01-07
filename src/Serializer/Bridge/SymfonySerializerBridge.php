<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Serializer\Bridge;

use Symfony\Component\Serializer;
use Desperado\Domain\Serializer\Exceptions as SerializerExceptions;
use Desperado\Domain\Serializer\SerializerInterface;
use Desperado\ServiceBus\Serializer\Bridge\Exceptions\UnexpectedSerializationFormatException;
use Symfony\Component\PropertyInfo;

/**
 * Symfony serializer bridge
 */
class SymfonySerializerBridge implements SerializerInterface
{
    private const AVAILABLE_FORMATS = [
        self::FORMAT_JSON
    ];

    /**
     * Symfony serializer
     *
     * @var Serializer\Serializer
     */
    private $symfonySerializer;

    /**
     * @inheritdoc
     */
    public function normalize($object): array
    {
        try
        {
            return $this
                ->getSerializer()
                ->normalize($object);
        }
        catch(\Exception $exception)
        {
            throw new SerializerExceptions\NormalizeException(
                \sprintf('Object normalize fail: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function encode(array $normalizedObject, string $classNamespace, string $format = self::FORMAT_JSON): string
    {
        try
        {
            self::guardFormat($format);

            return (string) $this
                ->getSerializer()
                ->encode($normalizedObject, $format);
        }
        catch(\Exception $exception)
        {
            throw new SerializerExceptions\SerializationException(
                \sprintf('Serialization fail: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function decode(string $encodedObject, string $format = self::FORMAT_JSON, array $context = []): array
    {
        try
        {
            self::guardFormat($format);

            return $this->getSerializer()->decode($encodedObject, $format);
        }
        catch(\Exception $exception)
        {
            throw new SerializerExceptions\SerializationException(
                \sprintf('Deserialization fail: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function denormalize(array $objectData, string $namespace)
    {
        try
        {
            return $this->getSerializer()->denormalize($objectData, $namespace);
        }
        catch(\Exception $exception)
        {
            throw new SerializerExceptions\DenormalizeException(
                \sprintf('Object denormalize fail: %s', $exception->getMessage()),
                $exception->getCode(),
                $exception
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
        if(null === $this->symfonySerializer)
        {
            $this->symfonySerializer = new Serializer\Serializer(
                self::getNormalizers(),
                self::getEncoders()
            );
        }

        return $this->symfonySerializer;
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
     * @return array
     */
    private static function getNormalizers(): array
    {
        return [
            new Serializer\Normalizer\ArrayDenormalizer(),
            new ObjectNormalizerProxy(
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
     * @throws UnexpectedSerializationFormatException
     */
    private static function guardFormat(string $format): string
    {
        $format = \strtolower($format);

        if('' === $format)
        {
            throw new UnexpectedSerializationFormatException('Serialization format must be specified');
        }

        if(false === \in_array($format, self::AVAILABLE_FORMATS, true))
        {
            throw new UnexpectedSerializationFormatException(
                \sprintf(
                    'Unsupported serialization format specified ("%s"). Available choices: %s',
                    $format, \implode(', ', \array_values(self::AVAILABLE_FORMATS))
                )
            );
        }

        return $format;
    }
}
