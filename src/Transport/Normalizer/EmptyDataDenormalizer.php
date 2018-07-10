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

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

/**
 * Denormalizer for an object without attributes (empty)
 */
final class EmptyDataDenormalizer implements DenormalizerInterface
{
    /**
     * @noinspection MoreThanThreeArgumentsInspection
     *
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = []): object
    {
        return new $class();
    }

    /**
     * @noinspection MoreThanThreeArgumentsInspection
     *
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
    {
        return \is_array($data) && 0 === \count($data);
    }
}