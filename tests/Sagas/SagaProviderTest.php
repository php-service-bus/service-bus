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

use Amp\Coroutine;
use function Amp\Promise\wait;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Saga;
use Desperado\ServiceBus\Sagas\SagaStore\SagasStore;
use Desperado\ServiceBus\Sagas\SagaStore\Sql\SQLSagaStore;
use Desperado\ServiceBus\Storage\StorageAdapter;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Tests\Sagas\Mocks\SimpleSagaSagaId;
use Desperado\ServiceBus\Tests\Sagas\Mocks\SomeCommand;
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
                (string) \file_get_contents(__DIR__ . '/../../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
            );

            $id   = SimpleSagaSagaId::new(TestSaga::class);
            $saga = new TestSaga($id);

            yield $self->provider->save($saga);
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
                (string) \file_get_contents(__DIR__ . '/../../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
            );

            $id = SimpleSagaSagaId::new(TestSaga::class);

            $context = new SagasContext();

            $saga = yield $self->provider->start($id, new SomeCommand(), $context);

            static::assertInstanceOf(Saga::class, $saga);
            static::assertCount(1, $context->messages);

            yield $self->provider->save($saga);

            $loadedSaga = yield $self->provider->obtain($id, $context);

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
                (string) \file_get_contents(__DIR__ . '/../../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
            );

            $id = SimpleSagaSagaId::new(TestSaga::class);

            yield $self->provider->start($id, new SomeCommand(), new SagasContext());
            yield $self->provider->start($id, new SomeCommand(), new SagasContext());
        };

        wait(new Coroutine($handler($this)));
    }
}
