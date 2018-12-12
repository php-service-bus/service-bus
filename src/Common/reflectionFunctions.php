<?php /** @noinspection ALL */

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Common;

/**
 * @param object $object
 * @param string $methodName
 * @param mixed  ...$parameters
 *
 * @return mixed
 *
 * @throws \ReflectionException
 */
function invokeReflectionMethod(object $object, string $methodName, ...$parameters)
{
    $reflectionMethod = new \ReflectionMethod($object, $methodName);
    $reflectionMethod->setAccessible(true);

    return $reflectionMethod->invoke($object, ...$parameters);
}

/**
 * Write value to property
 *
 * @param object $object
 * @param string $propertyName
 * @param mixed  $value
 *
 * @return void
 *
 * @throws \Throwable
 */
function writeReflectionPropertyValue(object $object, string $propertyName, $value): void
{
    $attribute = extractReflectionProperty($object, $propertyName);

    $attribute->setAccessible(true);
    $attribute->setValue($object, $value);
}

/**
 * Read property value
 *
 * @psalm-suppress MixedAssignment Mixed return data type
 *
 * @param object $object
 * @param string $propertyName
 *
 * @return mixed
 *
 * @throws \Throwable
 */
function readReflectionPropertyValue(object $object, string $propertyName)
{
    $attribute = extractReflectionProperty($object, $propertyName);

    $attribute->setAccessible(true);
    $value = $attribute->getValue($object);

    return $value;
}

/**
 * Extract property
 *
 * @param object $object
 * @param string $propertyName
 *
 * @return \ReflectionProperty
 *
 * @throws \Throwable
 */
function extractReflectionProperty(object $object, string $propertyName): \ReflectionProperty
{
    try
    {
        return new \ReflectionProperty($object, $propertyName);
    }
    catch(\ReflectionException $e)
    {
        $reflector = new \ReflectionObject($object);

        /** @noinspection LoopWhichDoesNotLoopInspection */
        while($reflector = $reflector->getParentClass())
        {
            try
            {
                return $reflector->getProperty($propertyName);
            }
            catch(\Throwable $throwable)
            {
                /** Not interested */
            }
        }

        throw new \ReflectionException(
            \sprintf('Property "%s" not exists in "%s"', $propertyName, \get_class($object))
        );
    }
}

/**
 * @param string $class
 *
 * @return object
 *
 * @throws \ReflectionException
 */
function createWithoutConstructor(string $class): object
{
    return (new \ReflectionClass($class))->newInstanceWithoutConstructor();
}
