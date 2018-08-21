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

use Desperado\ServiceBus\Sagas\SagaStatus;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class SagaStatusTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaStatus
     * @expectedExceptionMessage Incorrect saga status specified: qwerty
     *
     * @return void
     */
    public function withInvalidStatus(): void
    {
        SagaStatus::create('qwerty');
    }

    /**
     * @test
     *
     * @return void
     */
    public function expired(): void
    {
        static::assertEquals('expired', (string) SagaStatus::expired());
    }

    /**
     * @test
     *
     * @return void
     */
    public function failed(): void
    {
        static::assertEquals('failed', (string) SagaStatus::failed());
    }

    /**
     * @test
     *
     * @return void
     */
    public function completed(): void
    {
        static::assertEquals('completed', (string) SagaStatus::completed());
    }

    /**
     * @test
     *
     * @return void
     */
    public function created(): void
    {
        static::assertEquals('in_progress', (string) SagaStatus::created());
    }

    /**
     * @test
     *
     * @return void
     */
    public function equals(): void
    {
        static::assertTrue(SagaStatus::created()->equals(SagaStatus::created()));
    }
}
