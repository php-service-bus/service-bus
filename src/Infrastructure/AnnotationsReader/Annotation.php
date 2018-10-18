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

namespace Desperado\ServiceBus\Infrastructure\AnnotationsReader;

/**
 * Annotation data
 */
final class Annotation
{
    private const TYPE_CLASS_LEVEL  = 'class_level';
    private const TYPE_METHOD_LEVEL = 'method_level';

    /**
     * Annotation
     *
     * @var object
     */
    private $annotationObject;

    /**
     * Annotation type
     *
     * @see self::TYPE_*
     *
     * @var string
     */
    private $type;

    /**
     * The class containing the annotation
     *
     * @var string
     */
    private $inClass;

    /**
     * Specified if type = method_level
     *
     * @var \ReflectionMethod|null
     */
    private $reflectionMethod;

    /**
     * Creating a method level annotation VO
     *
     * @param \ReflectionMethod $method
     * @param object            $annotation
     * @param string            $inClass
     *
     * @return self
     */
    public static function methodLevel(\ReflectionMethod $method, object $annotation, string $inClass): self
    {
        $self = new self(self::TYPE_METHOD_LEVEL, $annotation, $inClass);

        $self->reflectionMethod = $method;

        return $self;
    }

    /**
     * Creating a method level annotation
     *
     * @param object $annotation
     * @param string $inClass
     *
     * @return self
     */
    public static function classLevel(object $annotation, string $inClass): self
    {
        return new self(self::TYPE_CLASS_LEVEL, $annotation, $inClass);
    }

    /**
     * Receive annotation object
     *
     * @return object
     */
    public function annotationObject(): object
    {
        return $this->annotationObject;
    }

    /**
     * Receive annotation type
     *
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * Receive containing the annotation class
     *
     * @return string
     */
    public function containingClass(): string
    {
        return $this->inClass;
    }

    /**
     * Receive reflection method (if method_level type)
     *
     * @return \ReflectionMethod|null
     */
    public function reflectionMethod(): ?\ReflectionMethod
    {
        return $this->reflectionMethod;
    }

    /**
     * Is a class-level annotation?
     *
     * @return bool
     */
    public function isClassLevel(): bool
    {
        return self::TYPE_CLASS_LEVEL === $this->type;
    }

    /**
     * @param string $type
     * @param object $annotation
     * @param string $inClass
     */
    private function __construct(string $type, object $annotation, string $inClass)
    {
        $this->type             = $type;
        $this->annotationObject = $annotation;
        $this->inClass          = $inClass;
    }
}
