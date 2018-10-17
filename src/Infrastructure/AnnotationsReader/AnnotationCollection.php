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
 * Annotations collection
 */
final class AnnotationCollection implements \Countable, \IteratorAggregate
{
    /**
     * Annotations VO
     *
     * @var array<string, \Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation>
     */
    private $collection = [];

    /**
     * @param array $annotations
     */
    public function __construct(array $annotations = [])
    {
        $this->push($annotations);
    }

    /**
     * Push multiple annotations
     *
     * @param array<mixed, \Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation> $annotations
     *
     * @return void
     */
    public function push(array $annotations): void
    {
        foreach($annotations as $annotation)
        {
            $this->add($annotation);
        }
    }

    /**
     * Add annotation data to collection
     *
     * @param Annotation $annotation
     *
     * @return void
     */
    public function add(Annotation $annotation): void
    {
        $this->collection[\spl_object_hash($annotation)] = $annotation;
    }

    /**
     * Map collection data
     *
     * @param callable<\Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation> $callable
     *
     * @return AnnotationCollection
     */
    public function map(callable $callable): AnnotationCollection
    {
        return new AnnotationCollection(\array_map($callable, $this->collection));
    }

    /**
     * Filter collection data
     *
     * @param callable<\Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation> $callable
     *
     * @return AnnotationCollection
     */
    public function filter(callable $callable): AnnotationCollection
    {
        return new AnnotationCollection(\array_filter($this->collection, $callable));
    }

    /**
     * Find all method-level annotations
     *
     * @return AnnotationCollection
     */
    public function methodLevelAnnotations(): AnnotationCollection
    {
        return $this->filter(
            static function(Annotation $annotation): ?Annotation
            {
                return false === $annotation->isClassLevel()
                    ? $annotation
                    : null;
            }
        );
    }

    /**
     * Find all class-level annotations
     *
     * @return AnnotationCollection
     */
    public function classLevelAnnotations(): AnnotationCollection
    {
        return $this->filter(
            static function(Annotation $annotation): ?Annotation
            {
                return true === $annotation->isClassLevel()
                    ? $annotation
                    : null;
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        return yield from $this->collection;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return \count($this->collection);
    }
}
