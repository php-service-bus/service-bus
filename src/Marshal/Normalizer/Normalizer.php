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

/**
 * Normalizer (execute convert object -> array)
 */
interface Normalizer
{
    /**
     * Normalization of the object (conversion into an array)
     *
     * @param object $object
     *
     * @return array
     *
     * @throws \Desperado\ServiceBus\Marshal\Normalizer\Exceptions\NormalizationFailed
     */
    public function normalize(object $object): array;
}
