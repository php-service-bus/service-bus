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

/**
 * Normalizer (execute convert array -> object)
 */
interface Denormalizer
{
    /**
     * Denormalization of an array (conversion into an object)
     *
     * @param string $class
     * @param array  $data
     *
     * @return object
     *
     * @throws \RuntimeException
     */
    public function denormalize(string $class, array $data): object;
}