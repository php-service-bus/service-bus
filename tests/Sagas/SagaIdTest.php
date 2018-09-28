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

namespace Desperado\ServiceBus\Tests\Sagas;

use Desperado\ServiceBus\Sagas\SagaId;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class SagaIdTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     * @expectedExceptionMessage The saga identifier can't be empty
     *
     * @return void
     */
    public function createWithEmptyIdValue(): void
    {
        new class('', __METHOD__) extends SagaId
        {

        };
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     * @expectedExceptionMessage Invalid saga class specified
     *                           ("Desperado\ServiceBus\Tests\Sagas\SagaIdTest::createWithWrongSagaClass")
     *
     * @return void
     */
    public function createWithWrongSagaClass(): void
    {
        new class('qwerty', __METHOD__) extends SagaId
        {

        };
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     * @expectedExceptionMessage Invalid saga class specified ("")
     *
     * @return void
     */
    public function createWithEmptySagaClass(): void
    {
        new class('qwerty', '') extends SagaId
        {

        };
    }
}
