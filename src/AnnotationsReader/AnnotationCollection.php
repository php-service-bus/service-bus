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

namespace Desperado\ServiceBus\AnnotationsReader;

/**
 * Annotations collection
 */
final class AnnotationCollection implements \Countable
{
    /**
     * Annotations VO
     *
     * @var array<string, \Desperado\ServiceBus\AnnotationsReader\Annotation>
     */
    private $collection;

    public function __construct()
    {
        /** @var array<empty, empty> collection */
        $this->collection = [];
    }

    /**
     * Push multiple annotations
     *
     * @param array<mixed, \Desperado\ServiceBus\AnnotationsReader\Annotation> $annotations
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
     * @param callable<\Desperado\ServiceBus\AnnotationsReader\Annotation> $callable
     *
     * @return array<mixed, \Desperado\ServiceBus\AnnotationsReader\Annotation>
     */
    public function map(callable $callable): array
    {
        return \array_map($callable, $this->collection);
    }

    /**
     * Filter collection data
     *
     * @param callable<\Desperado\ServiceBus\AnnotationsReader\Annotation> $callable
     *
     * @return array<mixed, \Desperado\ServiceBus\AnnotationsReader\Annotation>
     */
    public function filter(callable $callable): array
    {
        return \array_filter($this->collection, $callable);
    }

    /**
     * Find all method-level annotations
     *
     * @return array<mixed, \Desperado\ServiceBus\AnnotationsReader\Annotation>
     */
    public function methodLevelAnnotations(): array
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
     * @return array<mixed, \Desperado\ServiceBus\AnnotationsReader\Annotation>
     */
    public function classLevelAnnotations(): array
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
     * Receive all annotations
     *
     * @return array<mixed, \Desperado\ServiceBus\AnnotationsReader\Annotation>
     */
    public function all(): array
    {
        return $this->collection;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return \count($this->collection);
    }
}
