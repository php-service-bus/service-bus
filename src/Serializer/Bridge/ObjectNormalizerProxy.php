<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Serializer\Bridge;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * A wrapper over a symfony normalizer
 */
class ObjectNormalizerProxy extends ObjectNormalizer
{
    /**
     * {@inheritdoc}
     */
    protected function setAttributeValue($object, $attribute, $value, $format = null, array $context = [])
    {
        try
        {
            $this
                ->getReflectionProperty($object, $attribute)
                ->setValue($object, $value);
        }
        catch(\Throwable $throwable)
        {
            unset($throwable);
        }
    }

    /**
     * Get the object ReflectionProperty
     *
     * @param object|string $classOrObject
     * @param string        $attribute
     *
     * @return \ReflectionProperty
     *
     * @throws \Throwable
     */
    private function getReflectionProperty($classOrObject, string $attribute): \ReflectionProperty
    {
        $reflectionClass = new \ReflectionClass($classOrObject);

        while(true)
        {
            try
            {
                $reflectionProperty = $reflectionClass->getProperty($attribute);
                $reflectionProperty->setAccessible(true);

                return $reflectionProperty;
            }
            catch(\Throwable $throwable)
            {
                if(!$reflectionClass = $reflectionClass->getParentClass())
                {
                    throw $throwable;
                }
            }
        }
    }
}
