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

namespace Desperado\ServiceBus\Tests\Sagas\Annotations;

use Desperado\ServiceBus\Sagas\Annotations\SagaHeader;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class SagaHeaderTest extends TestCase
{

    /**
     * @test
     * @expectedException \InvalidArgumentException
     *
     * @return void
     */
    public function withWrongProperties(): void
    {
        new SagaHeader(['qwerty' => 'root']);
    }

    /**
     * @test
     *
     * @return void
     */
    public function withoutAnyProperties(): void
    {
        $annotation = new SagaHeader([]);

        static::assertFalse($annotation->hasIdClass());
        static::assertEmpty($annotation->idClass());

        static::assertFalse($annotation->hasContainingIdProperty());
        static::assertEmpty($annotation->containingIdProperty());

        static::assertEquals('+1 hour', $annotation->expireDateModifier());
    }

    /**
     * @test
     *
     * @return void
     */
    public function withFilledValues(): void
    {
        $annotation = new SagaHeader([
            'idClass'              => 'SomeClass',
            'containingIdProperty' => 'someProperty',
            'expireDateModifier'   => '+1 year'
        ]);

        static::assertTrue($annotation->hasIdClass());
        static::assertEquals('SomeClass', $annotation->idClass());

        static::assertTrue($annotation->hasContainingIdProperty());
        static::assertEquals('someProperty', $annotation->containingIdProperty());

        static::assertEquals('+1 year', $annotation->expireDateModifier());
    }
}
