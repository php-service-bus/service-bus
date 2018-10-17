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

namespace Desperado\ServiceBus\Tests\Infrastructure\AnnotationsReader;

use Desperado\ServiceBus\Infrastructure\AnnotationsReader\DefaultAnnotationsReader;
use Desperado\ServiceBus\Tests\Infrastructure\AnnotationsReader\Stubs\ClassWithCorrectAnnotations;
use Desperado\ServiceBus\Tests\Infrastructure\AnnotationsReader\Stubs\ClassWithIncorrectAnnotation;
use Desperado\ServiceBus\Tests\Infrastructure\AnnotationsReader\Stubs\TestClassLevelAnnotation;
use Desperado\ServiceBus\Tests\Infrastructure\AnnotationsReader\Stubs\TestMethodLevelAnnotation;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class DefaultAnnotationsReaderTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function parseEmptyClass(): void
    {
        $object = new class() {

        };

        $annotations = (new DefaultAnnotationsReader())->extract(\get_class($object));

        static::assertEmpty($annotations);
    }

    /**
     * @test
     *
     * @return void
     */
    public function parseClassWithAnnotations(): void
    {
        $annotations = (new DefaultAnnotationsReader())->extract(ClassWithCorrectAnnotations::class);

        static::assertNotEmpty($annotations);
        static::assertCount(2, $annotations);

        $classLevelAnnotations  = $annotations->classLevelAnnotations();
        $methodLevelAnnotations = $annotations->methodLevelAnnotations();

        static::assertCount(1, $classLevelAnnotations);
        static::assertCount(1, $methodLevelAnnotations);

        foreach($classLevelAnnotations as $annotation)
        {
            /** @var \Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation $annotation */

            static::assertNull($annotation->reflectionMethod());
            static::assertEquals(ClassWithCorrectAnnotations::class, $annotation->containingClass());
            static::assertEquals('class_level', $annotation->type());
            static::assertTrue($annotation->isClassLevel());
            /** @noinspection UnnecessaryAssertionInspection */
            static::assertInstanceOf(TestClassLevelAnnotation::class, $annotation->annotationObject());
        }

        foreach($methodLevelAnnotations as $annotation)
        {
            /** @var \Desperado\ServiceBus\Infrastructure\AnnotationsReader\Annotation $annotation */

            static::assertNotNull($annotation->reflectionMethod());
            static::assertEquals(ClassWithCorrectAnnotations::class, $annotation->containingClass());
            static::assertEquals('method_level', $annotation->type());
            static::assertFalse($annotation->isClassLevel());
            /** @noinspection UnnecessaryAssertionInspection */
            static::assertInstanceOf(TestMethodLevelAnnotation::class, $annotation->annotationObject());
        }
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Infrastructure\AnnotationsReader\ReadAnnotationFailed
     *
     * @return void
     */
    public function parseClassWithErrors(): void
    {
        (new DefaultAnnotationsReader())->extract(ClassWithIncorrectAnnotation::class);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Infrastructure\AnnotationsReader\ReadAnnotationFailed
     *
     * @return void
     */
    public function parseNotExistsClass(): void
    {
        (new DefaultAnnotationsReader())->extract('qwerty');
    }
}
