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

use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;
use Desperado\ServiceBus\Saga\Metadata\SagaMetadata;
use Desperado\ServiceBus\Saga\SagaState;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AbstractSagaTest extends TestCase
{
    /**
     * Saga instance
     *
     * @var TestSaga
     */
    private $saga;

    /**
     * Saga identity
     *
     * @var AbstractSagaIdentifier
     */
    private $sagaIdentifier;

    /**
     * @inheritdoc
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $sagaMetadata = SagaMetadata::create(
            TestSaga::class,
            '+1 day',
            TestSagaIdentifier::class,
            'requestId'
        );

        $this->sagaIdentifier = new TestSagaIdentifier('c30b5651-b702-4d6f-b1e1-14fd9812f1ca', TestSaga::class);
        $this->saga = new TestSaga($this->sagaIdentifier, $sagaMetadata);
    }

    /**
     * @inheritdoc
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->saga);
    }

    /**
     * @test
     *
     * @return void
     */
    public function initialization(): void
    {
        static::assertAttributeEquals('+1 day', 'expireDateModifier', $this->saga);
        static::assertAttributeEquals($this->sagaIdentifier, 'id', $this->saga);
        static::assertAttributeCount(1, 'events', $this->saga);
        static::assertAttributeCount(0, 'commands', $this->saga);

        static::assertAttributeNotEmpty('state', $this->saga);
        static::assertAttributeInstanceOf(SagaState::class, 'state', $this->saga);

        static::assertNotNull($this->saga->getCreatedAt());
        static::assertNull($this->saga->getClosedAt());

        static::assertEquals($this->sagaIdentifier->toString(), $this->saga->getIdentityAsString());
    }

    /**
     * @test
     *
     * @return void
     */
    public function flushEventsOnGetCollection(): void
    {
        static::assertAttributeCount(1, 'events', $this->saga);

        $this->saga->getEvents();

        static::assertAttributeCount(0, 'events', $this->saga);
    }

    /**
     * @test
     * @dataProvider changeStatusDataProvider
     *
     * @param string $changeStatusMethodName
     * @param string $reasonMessage
     * @param int    $expectedStatus
     *
     * @return void
     */
    public function changeStatus(
        string $changeStatusMethodName,
        string $reasonMessage,
        int $expectedStatus
    ): void
    {
        /** @var SagaState $currentStatus */
        $currentStatus = static::readAttribute($this->saga, 'state');

        static::assertEquals(SagaState::STATUS_IN_PROGRESS, $currentStatus->getStatusCode());

        $this->saga->$changeStatusMethodName($reasonMessage);

        /** @var SagaState $newStatus */
        $newStatus = static::readAttribute($this->saga, 'state');

        static::assertEquals($expectedStatus, $newStatus->getStatusCode());
        static::assertEquals($reasonMessage, $newStatus->getStatusReason());

        static::assertNotNull($this->saga->getClosedAt());
    }

    /**
     * @return array
     */
    public function changeStatusDataProvider(): array
    {
        return [
            ['closeCommand', 'test fail', SagaState::STATUS_FAILED],
            ['completeCommand', 'test success', SagaState::STATUS_COMPLETED],
            ['expireCommand', '', SagaState::STATUS_EXPIRED]
        ];
    }

    /**
     * @test
     *
     * @return void
     */
    public function transitionNonExistsEvent(): void
    {
        $event = new class() extends AbstractEvent
        {

        };

        $this->saga->transition($event);
    }


    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Exceptions\SagaIsClosedException
     *
     * @return void
     */
    public function transitionEventOnClosedSaga(): void
    {
        static::expectExceptionMessage(
            \sprintf('Saga "%s" is closed with status "4"', $this->sagaIdentifier->toString())
        );

        $this->saga->expireCommand();

        $event = new class() extends AbstractEvent
        {

        };

        $this->saga->transition($event);
    }

    /**
     * @test
     *
     * @return void
     */
    public function fireCommand(): void
    {
        $command = new class() extends AbstractCommand
        {

        };

        static::assertAttributeCount(0, 'commands', $this->saga);

        $this->saga->start($command);

        static::assertAttributeCount(1, 'commands', $this->saga);

        $commands = \iterator_to_array($this->saga->getCommands());
        $storedCommand = \end($commands);

        static::assertEquals($command, $storedCommand);

        static::assertAttributeCount(0, 'commands', $this->saga);
    }
}
