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

namespace Desperado\ServiceBus\MessageHandlers;

/**
 * Argument of the message handler
 */
final class HandlerArgument
{
    /**
     * @var \ReflectionParameter
     */
    private $reflectionParameter;

    /**
     * @param \ReflectionParameter $reflectionParameter
     */
    public function __construct(\ReflectionParameter $reflectionParameter)
    {
        $this->reflectionParameter = $reflectionParameter;
    }

    /**
     * Receive declaring class
     *
     * @return string
     */
    public function declaringClass(): string
    {
        /** @var \ReflectionClass $reflectionClass */
        $reflectionClass = $this->reflectionParameter->getDeclaringClass();

        return $reflectionClass->getName();
    }

    /**
     * Receive param name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->reflectionParameter->getName();
    }

    /**
     * Has specified type
     *
     * @return bool
     */
    public function hasType(): bool
    {
        return \is_object($this->reflectionParameter->getType());
    }

    /**
     * Receive the class name (in case the type is an object)
     *
     * @return string|null
     */
    public function className(): ?string
    {
        if(true === $this->isObject())
        {
            /** @var \ReflectionClass $reflectionClass */
            $reflectionClass = $this->reflectionParameter->getClass();

            return $reflectionClass->getName();
        }

        return null;
    }

    /**
     * Receive parameter type (null if a type is not specified)
     *
     * @return string|null
     */
    public function type(): ?string
    {
        if(true === $this->hasType())
        {
            /** @noinspection NullPointerExceptionInspection */
            /** @psalm-suppress PossiblyNullReference Type cannot be null */
            return true === \class_exists($this->reflectionParameter->getType()->getName())
                ? 'object'
                : $this->reflectionParameter->getType()->getName();
        }

        return null;
    }

    /**
     * The argument is an object
     *
     * @return bool
     */
    public function isObject(): bool
    {
        return $this->assertType('object');
    }

    /**
     * Checks if the class is of this class or has this class as one of its parents
     *
     * @param string $expectedClass
     *
     * @return bool
     */
    public function isA(string $expectedClass): bool
    {
        if(true === $this->isObject())
        {
            /** @var \ReflectionClass $reflectionClass */
            $reflectionClass = $this->reflectionParameter->getClass();

            return \is_a($reflectionClass->getName(), $expectedClass, true);
        }

        return false;
    }

    /**
     * Compare argument types
     *
     * @param string $expectedType
     *
     * @return bool
     */
    private function assertType(string $expectedType): bool
    {
        if(true === $this->hasType())
        {
            /** @var \ReflectionType $type */
            $type = $this->reflectionParameter->getType();

            if(true === \class_exists($type->getName()) || true === \interface_exists($type->getName()))
            {
                return 'object' === $expectedType;
            }

            return $expectedType === $type->getName();
        }

        return false;
    }
}
