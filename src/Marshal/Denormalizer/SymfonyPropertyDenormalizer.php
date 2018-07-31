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

namespace Desperado\ServiceBus\Marshal\Denormalizer;

use Desperado\ServiceBus\Marshal\Converters\SymfonyPropertyNameConverter;
use Desperado\ServiceBus\Marshal\Denormalizer\Extensions\EmptyDataDenormalizer;
use Desperado\ServiceBus\Marshal\Normalizer\Extensions\PropertyNormalizerWrapper;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer;

/**
 * Symfony denormalizer (property-based)
 */
final class SymfonyPropertyDenormalizer implements Denormalizer
{
    /**
     * Symfony serializer
     *
     * @var Serializer\Serializer
     */
    private $serializer;

    public function __construct()
    {
        $this->serializer = new Serializer\Serializer([
                new Serializer\Normalizer\ArrayDenormalizer(),
                new PropertyNormalizerWrapper(
                    null,
                    new SymfonyPropertyNameConverter(),
                    new PhpDocExtractor()
                ),
                new EmptyDataDenormalizer(),
                new Serializer\Normalizer\DateTimeNormalizer(
                    \DateTime::RFC3339,
                    new \DateTimeZone('UTC')
                )
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function denormalize(string $class, array $data): object
    {
        try
        {
            return $this->serializer->denormalize($data, $class);
        }
        catch(\Throwable $throwable)
        {
            throw new \RuntimeException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }
}
