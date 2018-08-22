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

namespace Desperado\ServiceBus\Tests\Common;

use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class ReadReflectionPropertyTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function readPublicProperty(): void
    {
        static::assertEquals(
            'abube',
            readReflectionPropertyValue(
                new SecondClass(),
                'secondClassPublicValue'
            )
        );
    }

    /**
     * @test
     * @expectedException \ReflectionException
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function readUnknownProperty(): void
    {
        readReflectionPropertyValue(new SecondClass(), 'qwerty');
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function readAllProperties(): void
    {
        $object = new SecondClass();

        static::assertEquals(
            'abube',
            readReflectionPropertyValue($object, 'secondClassPublicValue')
        );

        static::assertEquals(
            'root',
            readReflectionPropertyValue($object, 'secondClassValue')
        );

        static::assertEquals(
            'qwerty',
            readReflectionPropertyValue($object, 'firstClassValue')
        );
    }
}
