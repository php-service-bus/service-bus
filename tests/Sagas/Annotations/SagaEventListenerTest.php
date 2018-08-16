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

use Desperado\ServiceBus\Sagas\Annotations\SagaEventListener;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class SagaEventListenerTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function withContainingIdProperty(): void
    {
        $annotation = new SagaEventListener(['containingIdProperty' => 'qwerty']);

        static::assertTrue($annotation->hasContainingIdProperty());
        static::assertEquals('qwerty', $annotation->containingIdProperty());
    }

    /**
     * @test
     *
     * @return void
     */
    public function withoutContainingIdProperty(): void
    {
        $annotation = new SagaEventListener([]);

        static::assertFalse($annotation->hasContainingIdProperty());
        static::assertNull($annotation->containingIdProperty());
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     *
     * @return void
     */
    public function withWrongProperties(): void
    {
        new SagaEventListener(['qwerty' => 'root']);
    }
}
