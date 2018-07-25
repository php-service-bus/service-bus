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

use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use Desperado\ServiceBus\Sagas\SagaStatus;
use Desperado\ServiceBus\Tests\Sagas\Mocks\SimpleSaga;
use Desperado\ServiceBus\Tests\Sagas\Mocks\SimpleSagaSagaId;
use Desperado\ServiceBus\Tests\Sagas\Mocks\SomeCommand;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SagaTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\InvalidIdentifier
     * @expectedExceptionMessage  The class of the saga in the identifier
     *                            ("Desperado\Sagas\Tests\Domain\Stub\SimpleSaga") differs from the saga to which it
     *                            was transmitted ("SomeClass")
     *
     * @return void
     */
    public function createWithNotEqualsSagaClass(): void
    {
        new SimpleSaga(new SimpleSagaSagaId('123456789', \SomeClass::class));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successfulStart(): void
    {
        $id = new SimpleSagaSagaId('123456789', SimpleSaga::class);

        $saga = new SimpleSaga($id);
        $saga->start(new SomeCommand());
        $saga->doSomething();

        /** @var \DateTimeInterface $createdAt */
        $createdAt = readReflectionPropertyValue($saga, 'createdAt');

        static::assertNotFalse(\strtotime($createdAt->format('Y-m-d H:i:s')));

        static::assertEquals((string) $id, (string) $saga->id());

        $raisedEvents  = invokeReflectionMethod($saga, 'raisedEvents');
        $firedCommands = invokeReflectionMethod($saga, 'firedCommands');

        static::assertCount(1, $raisedEvents);
        static::assertCount(1, $firedCommands);

        $raisedEvents  = invokeReflectionMethod($saga, 'raisedEvents');
        $firedCommands = invokeReflectionMethod($saga, 'firedCommands');

        static::assertCount(0, $raisedEvents);
        static::assertCount(0, $firedCommands);

        static::assertEquals(SagaStatus::STATUS_IN_PROGRESS, (string) $saga->status());

        $saga->doSomethingElse();

        $raisedEvents  = invokeReflectionMethod($saga, 'raisedEvents');
        $firedCommands = invokeReflectionMethod($saga, 'firedCommands');

        static::assertCount(2, $raisedEvents);
        static::assertCount(0, $firedCommands);

        static::assertEquals(SagaStatus::STATUS_FAILED, (string) $saga->status());
    }

    /**
     * @test
     * @expectedException  \Desperado\ServiceBus\Sagas\Exceptions\ChangeSagaStateFailed
     * @expectedExceptionMessage  Changing the state of the saga is impossible: the saga is complete
     *
     * @return void
     */
    public function changeStateOnClosedSaga(): void
    {
        $id = new SimpleSagaSagaId('123456789', SimpleSaga::class);

        $saga = new SimpleSaga($id);
        $saga->start(new SomeCommand());

        $saga->doSomethingElse();
        $saga->closeWithSuccessStatus();
    }

    /**
     * @test
     *
     * @return void
     */
    public function changeStateToCompleted(): void
    {
        $id = new SimpleSagaSagaId('123456789', SimpleSaga::class);

        $saga = new SimpleSaga($id);
        $saga->start(new SomeCommand());
        $saga->closeWithSuccessStatus();

        static::assertEquals(SagaStatus::STATUS_COMPLETED, (string) $saga->status());
    }
}
