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

use Amp\Coroutine;
use function Amp\Promise\all;
use function Amp\Promise\wait;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Endpoint\EndpointRouter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation;
use Desperado\ServiceBus\Scheduler\Messages\Command\EmitSchedulerOperation;
use Desperado\ServiceBus\Scheduler\Messages\Event\OperationScheduled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationCanceled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationEmitted;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;
use Desperado\ServiceBus\Scheduler\SchedulerListener;
use Desperado\ServiceBus\Scheduler\Store\Sql\SqlSchedulerStore;
use Desperado\ServiceBus\SchedulerProvider;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Transport\VirtualIncomingPackage;
use Desperado\ServiceBus\Tests\Stubs\Transport\VirtualTransportEndpoint;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 *
 */
final class SchedulerListenerTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var TestHandler
     */
    private $logHandler;

    /**
     * @var StorageAdapter
     */
    private $adapter;

    /**
     * @var SqlSchedulerStore
     */
    private $schedulerStore;

    /**
     * @var SchedulerProvider
     */
    private $schedulerProvider;

    /**
     * @var KernelContext
     */
    private $context;

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->logHandler = new TestHandler();
        $this->logger     = new Logger('test', [$this->logHandler]);

        $this->adapter           = StorageAdapterFactory::inMemory();
        $this->schedulerStore    = new SqlSchedulerStore($this->adapter);
        $this->schedulerProvider = new SchedulerProvider($this->schedulerStore);

        $this->context = new KernelContext(
            new VirtualIncomingPackage(),
            new EndpointRouter(
                new VirtualTransportEndpoint()
            ),
            $this->logger
        );

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

        unset($this->logger, $this->logHandler, $this->adapter);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function emitNonExistsScheduledOperation(): void
    {
        $id      = ScheduledOperationId::new();
        $service = new SchedulerListener();

        $promise = $service->handleEmit(
            EmitSchedulerOperation::create($id),
            $this->context,
            $this->schedulerProvider
        );

        wait($promise);

        $records = $this->logHandler->getRecords();

        $logEntry = $records[0];

        static::assertEquals(
            \sprintf('Operation with ID "%s" not found', $id),
            $logEntry['message']
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function whenUnExistsOperationEmitted(): void
    {
        $generator = (new SchedulerListener())->whenSchedulerOperationEmitted(
            SchedulerOperationEmitted::create(ScheduledOperationId::new()),
            $this->context,
            $this->schedulerProvider
        );

        wait(new Coroutine($generator));

        static::assertEquals('Next operation not specified', $this->logHandler->getRecords()[0]['message']);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function whenSchedulerOperationCanceled(): void
    {
        $generator = (new SchedulerListener())->whenSchedulerOperationCanceled(
            SchedulerOperationCanceled::create(ScheduledOperationId::new(), 'reason', null),
            $this->context,
            $this->schedulerProvider
        );

        wait(new Coroutine($generator));

        static::assertEquals('Next operation not specified', $this->logHandler->getRecords()[0]['message']);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function whenOperationScheduled(): void
    {
        $generator = (new SchedulerListener())->whenOperationScheduled(
            OperationScheduled::create(ScheduledOperationId::new(), new FirstEmptyCommand(), new \DateTimeImmutable(), null),
            $this->context,
            $this->schedulerProvider
        );

        wait(new Coroutine($generator));

        static::assertEquals('Next operation not specified', $this->logHandler->getRecords()[0]['message']);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function emitWithNextOperation(): void
    {
        $service = new SchedulerListener();

        $firstScheduledOperationId  = ScheduledOperationId::new();
        $secondScheduledOperationId = ScheduledOperationId::new();

        $schedulePromises = [
            $this->schedulerProvider->schedule(
                $firstScheduledOperationId,
                new FirstEmptyCommand(),
                new \DateTimeImmutable('+1 hour'),
                $this->context
            ),
            $this->schedulerProvider->schedule(
                $secondScheduledOperationId,
                new SecondEmptyCommand(),
                new \DateTimeImmutable('+5 hour'),
                $this->context
            )
        ];

        wait(all($schedulePromises));

        $promise = $service->whenOperationScheduled(
            OperationScheduled::create(
                $firstScheduledOperationId,
                new FirstEmptyCommand(),
                new \DateTimeImmutable(),
                new NextScheduledOperation($secondScheduledOperationId, new \DateTimeImmutable('+5 hour'))
            ),
            $this->context,
            $this->schedulerProvider
        );

        wait(new Coroutine($promise));

        $records = $this->logHandler->getRecords();

        $logEntry = $records[5];

        static::assertEquals(
            'Scheduled operation with identifier "{scheduledOperationId}" will be executed in "{scheduledOperationDelay}" seconds',
            $logEntry['message']
        );
    }
}
