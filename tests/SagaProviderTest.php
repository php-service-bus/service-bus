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
use Desperado\ServiceBus\Tests\Sagas\Mocks\SimpleSagaSagaId;
use Desperado\ServiceBus\Tests\Sagas\Mocks\SomeCommand;
use Desperado\ServiceBus\Tests\Sagas\SagasContext;
use Desperado\ServiceBus\Tests\Sagas\SagaStore\Mocks\TestSaga;
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

            $id   = SimpleSagaSagaId::new(TestSaga::class);
            $saga = new TestSaga($id);

            yield $self->provider->save($saga, new SagasContext());
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

            $id = SimpleSagaSagaId::new(TestSaga::class);

            $context = new SagasContext();

            writeReflectionPropertyValue(
                $self->provider,
                'sagaMetaDataCollection',
                [
                    TestSaga::class => new SagaMetadata(
                        TestSaga::class,
                        SimpleSagaSagaId::class,
                        'requestId',
                        '+1 year'
                    )
                ]
            );

            /** @var TestSaga $saga */
            $saga = yield $self->provider->start($id, new SomeCommand(), $context);

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

            $id = SimpleSagaSagaId::new(TestSaga::class);

            writeReflectionPropertyValue(
                $self->provider,
                'sagaMetaDataCollection',
                [
                    TestSaga::class => new SagaMetadata(
                        TestSaga::class,
                        SimpleSagaSagaId::class,
                        'requestId',
                        '+1 year'
                    )
                ]
            );

            yield $self->provider->start($id, new SomeCommand(), new SagasContext());
            yield $self->provider->start($id, new SomeCommand(), new SagasContext());
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
                new TestSaga(SimpleSagaSagaId::new(TestSaga::class)),
                new SagasContext()
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

            $sagaId = SimpleSagaSagaId::new(TestSaga::class);

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

            $sagaId = SimpleSagaSagaId::new(TestSaga::class);

            yield $self->provider->start($sagaId, new SomeCommand(), new SagasContext());
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
                new TestSaga(SimpleSagaSagaId::new(TestSaga::class)),
                new SagasContext()
            );
        };

        wait(new Coroutine($handler($this)));
    }
}
