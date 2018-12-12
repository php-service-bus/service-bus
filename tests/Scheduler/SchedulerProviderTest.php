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

namespace Desperado\ServiceBus\Tests\Scheduler;

use function Amp\Promise\wait;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation;
use Desperado\ServiceBus\Scheduler\Data\ScheduledOperation;
use Desperado\ServiceBus\Scheduler\Messages\Event\OperationScheduled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationCanceled;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;
use Desperado\ServiceBus\Scheduler\Store\SchedulerStore;
use Desperado\ServiceBus\Scheduler\Store\Sql\SqlSchedulerStore;
use Desperado\ServiceBus\SchedulerProvider;
use function Desperado\ServiceBus\Infrastructure\Storage\fetchAll;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Tests\Stubs\Context\TestContext;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class SchedulerProviderTest extends TestCase
{
    /**
     * @var StorageAdapter
     */
    private $adapter;

    /**
     * @var SchedulerStore
     */
    private $store;

    /**
     * @var SchedulerProvider
     */
    private $provider;

    /**
     * @var array
     */
    private $kernelContextMessages;

    /**
     * @var TestHandler
     */
    private $testLogHandler;

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->kernelContextMessages = [];

        $this->adapter        = StorageAdapterFactory::inMemory();
        $this->store          = new SqlSchedulerStore($this->adapter);
        $this->provider       = new SchedulerProvider($this->store);
        $this->testLogHandler = new TestHandler();

        wait(
            $this->adapter->execute(
                (string) \file_get_contents(__DIR__ . '/../../src/Scheduler/Store/Sql/schema/scheduler_registry.sql')
            )
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->adapter, $this->store, $this->provider, $this->kernelContextMessages);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate
     * @expectedExceptionMessage The date of the scheduled task should be greater than the current one
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function scheduleWWithWrongDate(): void
    {
        wait(
            $this->provider->schedule(
                new ScheduledOperationId(uuid()),
                new FirstEmptyCommand(),
                new \DateTimeImmutable('-10 minutes'),
                new TestContext()
            )
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function schedule(): void
    {
        $executionDate = new \DateTimeImmutable('+10 seconds');
        $context       = new TestContext();
        $id            = new ScheduledOperationId(uuid());

        wait($this->provider->schedule($id, new FirstEmptyCommand(), $executionDate, $context));

        /** @var array $rows */
        $rows = wait(
            fetchAll(
                wait($this->adapter->execute('SELECT * FROM scheduler_registry'))
            )
        );

        static::assertNotNull($rows);
        static::assertNotEmpty($rows);
        static::assertCount(1, $rows);

        $rowData = \end($rows);

        $operation = ScheduledOperation::restoreFromRow($rowData);

        static::assertEquals($id, $operation->id);
        static::assertEquals($executionDate->format('c'), $operation->date->format('c'));
        static::assertTrue($operation->isSent);

        $messages = $context->messages;

        static::assertCount(1, $messages);

        /** @var \Desperado\ServiceBus\Scheduler\Messages\Event\OperationScheduled $message */
        $message = \end($messages);

        static::assertInstanceOf(OperationScheduled::class, $message);
        static::assertEquals(FirstEmptyCommand::class, $message->commandNamespace);
        static::assertTrue($message->hasNextOperation());
        static::assertEquals($executionDate->format('c'), $message->executionDate->format('c'));

        static::assertNotNull($message->nextOperation);

        /** @var NextScheduledOperation $nextOperation */
        $nextOperation = $message->nextOperation;

        static::assertInstanceOf(NextScheduledOperation::class, $nextOperation);
        static::assertEquals($id, $nextOperation->id);
        static::assertEquals($executionDate->format('c'), $nextOperation->time->format('c'));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Scheduler\Exceptions\DuplicateScheduledJob
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function scheduleDuplicate(): void
    {
        $executionDate = new \DateTimeImmutable('+10 seconds');
        $context       = new TestContext();
        $id            = new ScheduledOperationId(uuid());

        wait($this->provider->schedule($id, new FirstEmptyCommand(), $executionDate, $context));
        wait($this->provider->schedule($id, new FirstEmptyCommand(), $executionDate, $context));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successCancel(): void
    {
        $executionDate = new \DateTimeImmutable('+10 seconds');
        $context       = new TestContext();
        $id            = new ScheduledOperationId(uuid());

        wait($this->provider->schedule($id, new FirstEmptyCommand(), $executionDate, $context));
        wait($this->provider->cancel($id, $context, 'test reason'));

        /** @var array $rows */
        $rows = wait(
            fetchAll(
                wait($this->adapter->execute('SELECT * FROM scheduler_registry'))
            )
        );

        static::assertCount(0, $rows);

        $messages = $context->messages;

        static::assertNotEmpty($messages);
        static::assertCount(2, $messages);

        /** @var SchedulerOperationCanceled $message */
        $message = \end($messages);

        static::assertInstanceOf(SchedulerOperationCanceled::class, $message);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function cancelUnExistsJob(): void
    {
        $context = new TestContext();
        $id      = new ScheduledOperationId(uuid());

        wait($this->provider->cancel($id, $context, 'test reason')) ;

        $messages = $context->messages;

        static::assertNotEmpty($messages);
        static::assertCount(1, $messages);

        /** @var SchedulerOperationCanceled $message */
        $message = \reset($messages);

        static::assertInstanceOf(SchedulerOperationCanceled::class, $message);
    }
}
