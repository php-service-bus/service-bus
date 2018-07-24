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
 * @param object $object
 * @param string $propertyName
 *
 * @return mixed
 *
 * @throws \Throwable
 */
function readReflectionPropertyValue(object $object, string $propertyName)
{
    $attribute = null;

    try
    {
        $attribute = new \ReflectionProperty($object, $propertyName);
    }
    catch(\ReflectionException $e)
    {
        $reflector = new \ReflectionObject($object);

        while($reflector = $reflector->getParentClass())
        {
            try
            {
                $attribute = $reflector->getProperty($propertyName);

                break;
            }
            catch(\ReflectionException $e)
            {

            }
        }
    }

    if(null !== $attribute)
    {
        if(true === $attribute->isPublic())
        {
            return $object->{$attributeName};
        }

        $attribute->setAccessible(true);
        $value = $attribute->getValue($object);
        $attribute->setAccessible(false);

        return $value;
    }

    throw new \InvalidArgumentException(
        \sprintf(
            'Attribute "%s" not found in object.',
            $propertyName
        )
    );
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
