<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\Normalizer;

use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer;

/**
 * Symfony normalizer (property-based)
 */
final class SymfonyPropertyNormalizer implements Normalizer
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
                new Serializer\Normalizer\PropertyNormalizer(
                    null,
                    null,
                    new PhpDocExtractor()
                ),
                new EmptyDataNormalizer(),
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
    public function normalize(object $object): array
    {
        try
        {
            $data = $this->serializer->normalize($object);

            if(true === \is_array($data))
            {
                return $data;
            }

            throw new \UnexpectedValueException(
                \sprintf(
                    'The normalization was to return the array. Type "%s" was obtained when object "%s" was normalized',
                    \gettype($data),
                    \get_class($object)

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
