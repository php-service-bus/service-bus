<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Tests\Annotations;

use Desperado\ServiceBus\Annotations\AbstractAnnotation;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AbstractAnnotationTest extends TestCase
{

    /**
     * @test
     *
     * @return void
     */
    public function setExistsProperty(): void
    {
        $annotation = new TestAnnotation(['existsProperty' => 'value']);
        
        static::assertEquals('value', static::readAttribute($annotation, 'existsProperty'));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Annotations\UnknownAnnotationPropertyException
     *
     * @return void
     */
    public function setNonexistentProperty(): void
    {
        new TestAnnotation(['missedProperty' => 'qwerty']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function setOnConstructor(): void
    {
        $annotation = new class(['property' => 'value']) extends AbstractAnnotation
        {
            public $property;
        };

        static::assertEquals('value', $annotation->property);
    }
}
