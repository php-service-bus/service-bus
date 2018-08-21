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
use Desperado\ServiceBus\Sagas\Contract\SagaClosed;
use Desperado\ServiceBus\Sagas\Contract\SagaCreated;
use Desperado\ServiceBus\Sagas\Contract\SagaStatusChanged;
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

        static::assertTrue(
            $id->equals(
                readReflectionPropertyValue($saga, 'id')
            )
        );

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

        static::assertEquals(
            SagaStatus::STATUS_IN_PROGRESS,
            (string) readReflectionPropertyValue($saga, 'status')
        );

        $saga->doSomethingElse();

        $raisedEvents  = invokeReflectionMethod($saga, 'raisedEvents');
        $firedCommands = invokeReflectionMethod($saga, 'firedCommands');

        static::assertCount(3, $raisedEvents);
        static::assertCount(0, $firedCommands);

        static::assertEquals(
            SagaStatus::STATUS_FAILED,
            (string) readReflectionPropertyValue($saga, 'status')
        );
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
     *
     * @throws \Throwable
     */
    public function changeStateToCompleted(): void
    {
        $id = new SimpleSagaSagaId('123456789', SimpleSaga::class);

        $saga = new SimpleSaga($id);
        $saga->start(new SomeCommand());
        $saga->closeWithSuccessStatus();

        static::assertEquals(
            SagaStatus::STATUS_COMPLETED,
            (string) readReflectionPropertyValue($saga, 'status')
        );

        /** @var array<int, \Desperado\ServiceBus\Common\Contract\Messages\Event> $events */
        $events = \iterator_to_array(invokeReflectionMethod($saga, 'raisedEvents'));

        static::assertNotEmpty($events);
        static::assertCount(3, $events);

        /** @var \Desperado\ServiceBus\Sagas\Contract\SagaStatusChanged $changedStatusEvent */
        $changedStatusEvent = $events[1];

        static::assertInstanceOf(SagaStatusChanged::class, $changedStatusEvent);
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(\DateTimeImmutable::class, $changedStatusEvent->datetime());
        static::assertEquals((string) $id, $changedStatusEvent->id());
        static::assertEquals(\get_class($id), $changedStatusEvent->idClass());
        static::assertEquals(SimpleSaga::class, $changedStatusEvent->sagaClass());
        static::assertTrue(SagaStatus::created()->equals(SagaStatus::create($changedStatusEvent->previousStatus())));
        static::assertTrue(SagaStatus::completed()->equals(SagaStatus::create($changedStatusEvent->newStatus())));
        static::assertNull($changedStatusEvent->withReason());

        /** @var \Desperado\ServiceBus\Sagas\Contract\SagaClosed $sagaClosedEvent */
        $sagaClosedEvent = $events[2];

        static::assertInstanceOf(SagaClosed::class, $sagaClosedEvent);
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(\DateTimeImmutable::class, $sagaClosedEvent->datetime());
        static::assertEquals((string) $id, $sagaClosedEvent->id());
        static::assertEquals(\get_class($id), $sagaClosedEvent->idClass());
        static::assertEquals(SimpleSaga::class, $sagaClosedEvent->sagaClass());
        static::assertNull($sagaClosedEvent->withReason());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function sagaCreated(): void
    {
        $id   = new SimpleSagaSagaId('123456789', SimpleSaga::class);
        $saga = new SimpleSaga($id);
        $saga->start(new SomeCommand());

        /** @var array<int, \Desperado\ServiceBus\Common\Contract\Messages\Event> $events */
        $events = \iterator_to_array(invokeReflectionMethod($saga, 'raisedEvents'));

        static::assertNotEmpty($events);
        static::assertCount(1, $events);

        /** @var \Desperado\ServiceBus\Sagas\Contract\SagaCreated $sagaCreatedEvent */
        $sagaCreatedEvent = \end($events);

        static::assertInstanceOf(SagaCreated::class, $sagaCreatedEvent);
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(\DateTimeImmutable::class, $sagaCreatedEvent->datetime());
        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(\DateTimeImmutable::class, $sagaCreatedEvent->expirationDate());
        static::assertEquals((string) $id, $sagaCreatedEvent->id());
        static::assertEquals(\get_class($id), $sagaCreatedEvent->idClass());
        static::assertEquals(SimpleSaga::class, $sagaCreatedEvent->sagaClass());
    }
}
