<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Common\Utils;

/**
 * Reflection utils
 */
class ReflectionUtils
{
    /**
     * Get all non-static/non-abstract class methods
     *
     * @param object|string $object
     *
     * @return \ReflectionMethod[]
     */
    public static function getMethods($object): array
    {
        return self::getClassMethods(
            $object,
            \ReflectionMethod::IS_PUBLIC |
            \ReflectionMethod::IS_PROTECTED |
            \ReflectionMethod::IS_PRIVATE
        );
    }

    /**
     * Get all class public methods
     *
     * @param object|string $object
     *
     * @return \ReflectionMethod[]
     */
    public static function getPublicMethods($object): array
    {
        return self::getClassMethods($object, \ReflectionMethod::IS_PUBLIC);
    }

    /**
     * Get all class protected methods
     *
     * @param object|string $object
     *
     * @return \ReflectionMethod[]
     */
    public static function getProtectedMethods($object): array
    {
        return self::getClassMethods($object, \ReflectionMethod::IS_PROTECTED);
    }

    /**
     * Get property value
     *
     * @param object $object
     * @param string $propertyName
     *
     * @return mixed
     */
    public static function getPropertyValue($object, string $propertyName)
    {
        $reflectionProperty = new \ReflectionProperty(\get_class($object), $propertyName);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    /**
     * Get all class methods by specified filter
     *
     * @param string|object $classOrObject
     * @param int|null      $methodFlag
     *
     * @return \ReflectionMethod[]
     */
    private static function getClassMethods($classOrObject, int $methodFlag = null)
    {
        $objectNamespace = true === \is_object($classOrObject)
            ? \get_class($classOrObject)
            : $classOrObject;

        return (new \ReflectionClass($objectNamespace))
            ->getMethods($methodFlag);
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
