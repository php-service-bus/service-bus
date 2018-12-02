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

namespace Desperado\ServiceBus\Infrastructure\MessageSerialization\Symfony\Extensions;

use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

/**
 * Disable the use of the constructor
 *
 * @noinspection LongInheritanceChainInspection
 */
final class PropertyNormalizerWrapper extends PropertyNormalizer
{
    /**
     * @inheritdoc
     *
     * @psalm-suppress MissingParamType Cannot specify data type
     */
    protected function instantiateObject(
        array &$data,
        $class,
        array &$context,
        \ReflectionClass $reflectionClass,
        $allowedAttributes,
        string $format = null
    ): object
    {
        return $reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheableSupportsMethod(): bool
    {
        return true;
    }

    /**
     * @psalm-suppress MissingParamType Cannot specify data type
     * @psalm-suppress MixedArgument Cannot specify data type
     * @psalm-suppress MissingReturnType Mixed return type
     *
     * {@inheritdoc}
     */
    protected function getAttributeValue($object, $attribute, $format = null, array $context = [])
    {
        $extractClosure = \Closure::bind(
        /** @psalm-suppress MissingClosureReturnType Mixed return type */
            function() use ($attribute)
            {
                return true === isset($this->{$attribute}) ? $this->{$attribute} : null;
            },
            $object, $object
        );

        return $extractClosure($object);
    }

    /**
     * @psalm-suppress MissingParamType Cannot specify data type
     * @psalm-suppress MixedArgument Cannot specify data type
     *
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = []): void
    {
        $extractClosure = \Closure::bind(
            function() use ($attribute, $value): void
            {
                $this->{$attribute} = $value;
            },
            $object, $object
        );

        $extractClosure($object);
    }
}
