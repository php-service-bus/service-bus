<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Scheduler;

use Desperado\Domain\DateTime;
use Desperado\Domain\Message\AbstractCommand;
use Desperado\ServiceBus\Scheduler\Events;
use Desperado\ServiceBus\Scheduler\Identifier\ScheduledCommandIdentifier;
use Desperado\ServiceBus\Scheduler\NextScheduledOperation;
use Desperado\ServiceBus\Scheduler\SchedulerProvider;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineConnectionFactory;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineSchedulerStorage;
use Desperado\ServiceBus\Tests\Saga\LocalDeliveryContext;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class SchedulerProviderTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DoctrineSchedulerStorage
     */
    private $storage;

    /**
     * @var SchedulerProvider
     */
    private $provider;

    /**
     * @var LocalDeliveryContext
     */
    private $context;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DoctrineConnectionFactory::create('sqlite:///:memory:');
        $this->storage = new DoctrineSchedulerStorage($this->connection);
        $this->provider = new SchedulerProvider($this->storage);
        $this->context = new LocalDeliveryContext();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->connection->close();

        unset($this->connection, $this->storage, $this->provider);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDateException
     * @expectedExceptionMessage Scheduled operation date must be greater then now
     *
     * @return void
     */
    public function failedDateScheduleCommand(): void
    {
        $this->provider->scheduleCommand(
            ScheduledCommandIdentifier::new(),
            SchedulerTestCommand::create(),
            DateTime::fromString('-1 day'),
            $this->context
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function successScheduleCommand(): void
    {
        $id = ScheduledCommandIdentifier::new();
        $date = DateTime::now();

        $this->provider->scheduleCommand(
            $id,
            SchedulerTestCommand::create(),
            $date,
            $this->context
        );

        $events = $this->context->getPublishedEvents();

        static::assertCount(1, $events);

        /** @var Events\OperationScheduledEvent $event */
        $event = \end($events);

        static::assertInstanceOf(Events\OperationScheduledEvent::class, $event);
        static::assertEquals($id->toString(), $event->getId());
        static::assertEquals(SchedulerTestCommand::class, $event->getCommandNamespace());
        static::assertEquals((string) $date, $event->getExecutionDate());
        static::assertNotNull($event->getNextOperation());
        static::assertInstanceOf(NextScheduledOperation::class, $event->getNextOperation());
        static::assertEquals($id->toString(), $event->getNextOperation()->getId());
        static::assertGreaterThan(0, $event->getNextOperation()->getTime());
    }

    /**
     * @test
     *
     * @return void
     */
    public function successEmitCommand(): void
    {
        $id = ScheduledCommandIdentifier::new();

        $this->provider->scheduleCommand(
            $id,
            SchedulerTestCommand::create(),
            DateTime::now(),
            $this->context
        );

        static::assertCount(1, $this->context->getPublishedEvents());

        $this->provider->emitCommand($id, $this->context);

        static::assertCount(1, $this->context->getPublishedCommands());
        static::assertCount(2, $this->context->getPublishedEvents());

        $commands = $this->context->getPublishedCommands();
        /** @var AbstractCommand $command */
        $command = \end($commands);

        static::assertInstanceOf(SchedulerTestCommand::class, $command);

        $events = $this->context->getPublishedEvents();
        /** @var Events\SchedulerOperationEmittedEvent $event */
        $event = \end($events);

        static::assertEquals($id->toString(), $event->getId());
        static::assertNull($event->getNextOperation());
    }

    /**
     * @test
     *
     * @return void
     */
    public function successCancelCommand(): void
    {
        $id = ScheduledCommandIdentifier::new();

        $this->provider->scheduleCommand(
            $id,
            SchedulerTestCommand::create(),
            DateTime::now(),
            $this->context
        );

        $this->provider->cancelScheduledCommand($id, $this->context, 'testReason');

        $events = $this->context->getPublishedEvents();
        /** @var Events\SchedulerOperationCanceledEvent $event */
        $event = \end($events);

        static::assertInstanceOf(Events\SchedulerOperationCanceledEvent::class, $event);
        static::assertEquals($id->toString(), $event->getId());
        static::assertEquals('testReason', $event->getReason());
        static::assertNull($event->getNextOperation());

        static::assertNull($this->storage->load($id->toString()));
    }
}
