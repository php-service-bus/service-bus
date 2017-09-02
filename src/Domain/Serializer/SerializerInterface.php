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

namespace Desperado\Framework\Domain\Serializer;

/**
 * Serializer
 */
interface SerializerInterface
{
    public const FORMAT_JSON = 'json';

    /**
     * Encode object normalized data
     *
     * @param array  $normalizedObject
     * @param string $classNamespace
     * @param string $format
     *
     * @return string
     *
     * @throws \Desperado\Framework\Domain\Serializer\Exceptions\SerializationException
     */
    public function encode(array $normalizedObject, string $classNamespace, string $format): string;

    /**
     * Decode data
     *
     * @param string $encodedObject
     * @param string $format
     * @param array  $context
     *
     * @return array
     *
     * @throws \Desperado\Framework\Domain\Serializer\Exceptions\SerializationException
     */
    public function decode(string $encodedObject, string $format, array $context = []): array;

    /**
     * Normalizes an object into array
     *
     * @param object $object
     *
     * @return array
     *
     * @throws \Desperado\Framework\Domain\Serializer\Exceptions\NormalizeException
     */
    public function normalize($object): array;

    /**
     * Denormalizes data back into an object of the given class
     *
     * @param array  $objectData
     * @param string $namespace
     *
     * @return object
     *
     * @throws \Desperado\Framework\Domain\Serializer\Exceptions\DenormalizeException
     */
    public function denormalize(array $objectData, string $namespace);
}
