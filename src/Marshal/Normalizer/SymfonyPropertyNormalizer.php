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

namespace Desperado\ServiceBus\Marshal\Normalizer;

use Desperado\ServiceBus\Marshal\Normalizer\Extensions\EmptyDataNormalizer;
use Desperado\ServiceBus\Marshal\Normalizer\Extensions\PropertyNormalizerWrapper;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\Serializer;

/**
 * Symfony normalizer (property-based)
 */
class SymfonyPropertyNormalizer
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
                    null,
                    new PhpDocExtractor()
                ),
                new EmptyDataNormalizer(),
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


}
