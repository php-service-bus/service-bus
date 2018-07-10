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

/**
 * Normalizer (execute convert object -> array -> object)
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
     * @throws \RuntimeException
     */
    public function normalize(object $object): array;

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
