<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga;

use Desperado\Domain\DateTime;
use Desperado\ServiceBus\Saga\SagaState;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SagaStateTest extends TestCase
{
    /**
     * @test
     *
     * @return SagaState
     */
    public function createInProgress(): SagaState
    {
        $createdAt = DateTime::fromString('2017-01-01');
        $expireDate = DateTime::fromString('2018-01-01');

        $sagaState = SagaState::create($createdAt, $expireDate);

        static::assertFalse($sagaState->isClosed());
        static::assertFalse($sagaState->isSuccess());
        static::assertFalse($sagaState->isExpired());
        static::assertFalse($sagaState->isFailed());

        static::assertTrue($sagaState->isProcessing());

        static::assertEquals(SagaState::STATUS_IN_PROGRESS, $sagaState->getStatusCode());
        static::assertEquals((string) $createdAt, (string) $sagaState->getCreatedAt());
        static::assertEquals((string) $expireDate, (string) $sagaState->getExpireDate());

        static::assertEmpty($sagaState->getStatusReason());

        static::assertNull($sagaState->getExpiredAt());
        static::assertNull($sagaState->getClosedAt());

        return $sagaState;
    }

    /**
     * @test
     * @depends createInProgress
     *
     * @param SagaState $sagaState
     *
     * @return void
     */
    public function changeStateToExpire(SagaState $sagaState): void
    {
        $expiredAt = DateTime::fromString('2017-02-02');

        $sagaState = $sagaState->expire($expiredAt);

        static::assertInstanceOf(SagaState::class, $sagaState);

        static::assertTrue($sagaState->isClosed());
        static::assertTrue($sagaState->isExpired());
        static::assertTrue($sagaState->isFailed());

        static::assertFalse($sagaState->isSuccess());
        static::assertFalse($sagaState->isProcessing());

        static::assertEquals(SagaState::STATUS_EXPIRED, $sagaState->getStatusCode());
        static::assertEquals((string) $expiredAt, (string) $sagaState->getExpiredAt());
        static::assertEquals((string) $expiredAt, (string) $sagaState->getClosedAt());
    }

    /**
     * @test
     * @depends createInProgress
     *
     * @param SagaState $sagaState
     *
     * @return void
     */
    public function changeStateToFail(SagaState $sagaState): void
    {
        $closedAt = DateTime::fromString('2017-02-02');

        $sagaState = $sagaState->fail('Test reason', $closedAt);

        static::assertTrue($sagaState->isClosed());
        static::assertTrue($sagaState->isFailed());

        static::assertFalse($sagaState->isExpired());
        static::assertFalse($sagaState->isSuccess());
        static::assertFalse($sagaState->isProcessing());

        static::assertEquals(SagaState::STATUS_FAILED, $sagaState->getStatusCode());
        static::assertEquals('Test reason', $sagaState->getStatusReason());
        static::assertEquals((string) $closedAt, (string) $sagaState->getClosedAt());

        static::assertNull($sagaState->getExpiredAt());
    }

    /**
     * @test
     * @depends createInProgress
     *
     * @param SagaState $sagaState
     *
     * @return void
     */
    public function changeStateToComplete(SagaState $sagaState): void
    {
        $closedAt = DateTime::fromString('2017-02-02');

        $sagaState = $sagaState->complete($closedAt, 'comment');

        static::assertTrue($sagaState->isClosed());
        static::assertTrue($sagaState->isSuccess());

        static::assertFalse($sagaState->isExpired());
        static::assertFalse($sagaState->isProcessing());
        static::assertFalse($sagaState->isFailed());

        static::assertEquals(SagaState::STATUS_COMPLETED, $sagaState->getStatusCode());
        static::assertEquals((string) $closedAt, $sagaState->getClosedAt());

        static::assertNull($sagaState->getExpiredAt());
    }
}
