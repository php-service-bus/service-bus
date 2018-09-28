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

namespace Desperado\ServiceBus\Tests;

use Amp\Coroutine;
use function Amp\Promise\wait;
use function Desperado\ServiceBus\Common\writeReflectionPropertyValue;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Configuration\SagaMetadata;
use Desperado\ServiceBus\Sagas\Exceptions\LoadSagaFailed;
use Desperado\ServiceBus\Sagas\Exceptions\SaveSagaFailed;
use Desperado\ServiceBus\Sagas\Exceptions\StartSagaFailed;
use Desperado\ServiceBus\Sagas\Saga;
use Desperado\ServiceBus\Sagas\SagaStore\SagasStore;
use Desperado\ServiceBus\Sagas\SagaStore\Sql\SQLSagaStore;
use Desperado\ServiceBus\Storage\StorageAdapter;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Tests\Stubs\Context\TestContext;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Sagas\CorrectSaga;
use Desperado\ServiceBus\Tests\Stubs\Sagas\TestSagaId;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class SagaProviderTest extends TestCase
{
    /**
     * @var StorageAdapter
     */
    private $adapter;

    /**
     * @var SagasStore
     */
    private $store;

    /**
     * @var SagaProvider
     */
    private $provider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->adapter  = StorageAdapterFactory::inMemory();
        $this->store    = new SQLSagaStore($this->adapter);
        $this->provider = new SagaProvider($this->store);
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->provider, $this->store, $this->adapter);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\SaveSagaFailed
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveNotExistsSaga(): void
    {
        $handler = static function(SagaProviderTest $self): \Generator
        {
            yield $self->adapter->execute(
                (string) \file_get_contents(__DIR__ . '/../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
            );

            $id   = TestSagaId::new(CorrectSaga::class);
            $saga = new CorrectSaga($id);

            yield $self->provider->save($saga, new TestContext());
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function flow(): void
    {
        $handler = static function(SagaProviderTest $self): \Generator
        {
            yield $self->adapter->execute(
                (string) \file_get_contents(__DIR__ . '/../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
            );

            $id = TestSagaId::new(CorrectSaga::class);

            $context = new TestContext();

            writeReflectionPropertyValue(
                $self->provider,
                'sagaMetaDataCollection',
                [
                    CorrectSaga::class => new SagaMetadata(
                        CorrectSaga::class,
                        TestSagaId::class,
                        'requestId',
                        '+1 year'
                    )
                ]
            );

            /** @var CorrectSaga $saga */
            $saga = yield $self->provider->start($id, new FirstEmptyCommand(), $context);

            static::assertInstanceOf(Saga::class, $saga);
            static::assertCount(1, $context->messages);

            yield $self->provider->save($saga, $context);

            $loadedSaga = yield $self->provider->obtain($id);

            static::assertInstanceOf(Saga::class, $loadedSaga);
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\DuplicateSagaId
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveDuplicateSaga(): void
    {
        $handler = static function(SagaProviderTest $self): \Generator
        {
            yield $self->adapter->execute(
                (string) \file_get_contents(__DIR__ . '/../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
            );

            $id = TestSagaId::new(CorrectSaga::class);

            writeReflectionPropertyValue(
                $self->provider,
                'sagaMetaDataCollection',
                [
                    CorrectSaga::class => new SagaMetadata(
                        CorrectSaga::class,
                        TestSagaId::class,
                        'requestId',
                        '+1 year'
                    )
                ]
            );

            yield $self->provider->start($id, new FirstEmptyCommand(), new TestContext());
            yield $self->provider->start($id, new FirstEmptyCommand(), new TestContext());
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveWithoutSchema(): void
    {
        $handler = static function(SagaProviderTest $self): \Generator
        {
            $self->expectException(SaveSagaFailed::class);

            yield $self->provider->save(
                new CorrectSaga(TestSagaId::new(CorrectSaga::class)),
                new TestContext()
            );
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function loadWithoutSchema(): void
    {
        $handler = static function(SagaProviderTest $self): \Generator
        {
            $self->expectException(LoadSagaFailed::class);

            $sagaId = TestSagaId::new(CorrectSaga::class);

            yield $self->provider->obtain($sagaId);
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function startWithoutSchema(): void
    {
        $handler = static function(SagaProviderTest $self): \Generator
        {
            $self->expectException(StartSagaFailed::class);

            $sagaId = TestSagaId::new(CorrectSaga::class);

            yield $self->provider->start($sagaId, new FirstEmptyCommand(), new TestContext());
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveUnStartedSaga(): void
    {
        $handler = static function(SagaProviderTest $self): \Generator
        {
            $self->expectException(SaveSagaFailed::class);

            yield $self->adapter->execute(
                (string) \file_get_contents(__DIR__ . '/../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
            );

            yield $self->provider->save(
                new CorrectSaga(TestSagaId::new(CorrectSaga::class)),
                new TestContext()
            );
        };

        wait(new Coroutine($handler($this)));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\StartSagaFailed
     * @expectedExceptionMessage The expiration date of the saga can not be less than the current date
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function startWithWrongExpirationInterval(): void
    {
        $handler = static function(SagaProviderTest $self): \Generator
        {
            $id = TestSagaId::new(CorrectSaga::class);

            $context = new TestContext();

            writeReflectionPropertyValue(
                $self->provider,
                'sagaMetaDataCollection',
                [
                    CorrectSaga::class => new SagaMetadata(
                        CorrectSaga::class,
                        TestSagaId::class,
                        'requestId',
                        '-1 year'
                    )
                ]
            );

            yield $self->provider->start($id, new FirstEmptyCommand(), $context);
        };

        wait(new Coroutine($handler($this)));
    }
}
