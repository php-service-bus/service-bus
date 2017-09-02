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

namespace Desperado\ConcurrencyFramework\Infrastructure\Bridge\Serializer;

use Desperado\ConcurrencyFramework\Domain\Serializer\Exceptions;
use Desperado\ConcurrencyFramework\Domain\Serializer\SerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Serializer\Exceptions\UnexpectedSerializationFormatException;
use Symfony\Component\PropertyInfo;
use Symfony\Component\Serializer;

/**
 * Symfony serializer
 */
class SymfonySerializer implements SerializerInterface
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
            return $this->getSerializer()->normalize($object);
        }
        catch(\Exception $exception)
        {
            throw new Exceptions\NormalizeException('Object normalize fail', $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritdoc
     */
    public function encode(array $normalizedObject, string $classNamespace, string $format): string
    {
        try
        {
            self::guardFormat($format);

            return $this->getSerializer()->encode($normalizedObject, self::FORMAT_JSON);
        }
        catch(\Exception $exception)
        {
            throw new Exceptions\SerializationException('Serialization fail', $exception->getCode(), $exception);
        }
    }

    /**
     * @inheritdoc
     */
    public function decode(string $encodedObject, string $format, array $context = []): array
    {
        try
        {
            self::guardFormat($format);

            return $this->getSerializer()->decode($encodedObject, $format);
        }
        catch(\Exception $exception)
        {
            throw new Exceptions\SerializationException('Deserialization fail', $exception->getCode(), $exception);
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
            throw new Exceptions\DenormalizeException('Object denormalize fail', $exception->getCode(), $exception);
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
