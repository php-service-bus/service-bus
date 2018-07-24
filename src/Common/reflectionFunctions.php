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
 * @throws \ReflectionException
 */
function readPropertyValue(object $object, string $propertyName)
{
    $reflectionProperty = new \ReflectionProperty($object, $propertyName);
    $reflectionProperty->setAccessible(true);

    return $reflectionProperty->getValue($object);
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
