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
use Desperado\Domain\Uuid;
use Desperado\ServiceBus\Scheduler\Identifier\ScheduledCommandIdentifier;
use Desperado\ServiceBus\Scheduler\NextScheduledOperation;
use Desperado\ServiceBus\Scheduler\SchedulerListenerService;
use Desperado\ServiceBus\Scheduler\SchedulerProvider;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineConnectionFactory;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineSchedulerStorage;
use Desperado\ServiceBus\Tests\Saga\LocalDeliveryContext;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Desperado\ServiceBus\Scheduler\Events;
use Desperado\ServiceBus\Scheduler\Commands;

/**
 *
 */
class SchedulerListenerServiceTest extends TestCase
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
     * @var SchedulerListenerService
     */
    private $service;

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
        $this->service = new SchedulerListenerService('phpUnit', $this->provider);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->connection->close();

        unset($this->connection, $this->storage, $this->provider, $this->service);
    }

    /**
     * @test
     *
     * @return void
     */
    public function emptyWhenSchedulerOperationEmittedEvent(): void
    {
        $this->service->whenSchedulerOperationEmittedEvent(
            Events\SchedulerOperationEmittedEvent::create(['id' => Uuid::v4()]),
            $this->context
        );

        static::assertEmpty($this->context->getPublishedCommands());
        static::assertEmpty($this->context->getPublishedEvents());
    }

    /**
     * @test
     *
     * @return void
     */
    public function whenSchedulerOperationEmittedEvent(): void
    {
        $id = ScheduledCommandIdentifier::new();

        $this->provider->scheduleCommand(
            $id,
            SchedulerTestCommand::create(),
            DateTime::fromString('+1 day'),
            $this->context
        );

        $this->service->whenSchedulerOperationEmittedEvent(
            Events\SchedulerOperationEmittedEvent::create(['id' => $id->toString()]),
            $this->context
        );

        static::assertEmpty($this->context->getPublishedCommands());
        static::assertNotEmpty($this->context->getPublishedEvents());
        static::assertCount(1, $this->context->getPublishedEvents());
    }

    /**
     * @test
     *
     * @return void
     */
    public function whenSchedulerOperationCanceledEvent(): void
    {
        $this->service->whenSchedulerOperationCanceledEvent(
            Events\SchedulerOperationCanceledEvent::create(['id' => Uuid::v4()]),
            $this->context
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function whenOperationScheduledEvent(): void
    {
        $this->service->whenOperationScheduledEvent(
            Events\OperationScheduledEvent::create(['id' => Uuid::v4()]),
            $this->context
        );
    }


    /**
     * @test
     *
     * @return void
     */
    public function whenSchedulerOperationCanceledEventWithNextCommand(): void
    {
        $nextOperationId = Uuid::v4();

        $this->service->whenSchedulerOperationCanceledEvent(
            Events\SchedulerOperationCanceledEvent::create([
                    'id'            => Uuid::v4(),
                    'nextOperation' => new NextScheduledOperation($nextOperationId, time())
                ]
            ),
            $this->context
        );

        static::assertNotEmpty($this->context->getPublishedCommands());
        static::assertCount(1, $this->context->getPublishedCommands());

        $commands = $this->context->getPublishedCommands();
        /** @var Commands\EmitSchedulerOperationCommand $command */
        $command = \end($commands);

        static::assertInstanceOf(Commands\EmitSchedulerOperationCommand::class, $command);
        static::assertEquals($nextOperationId, $command->getId());
    }
}
