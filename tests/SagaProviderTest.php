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

use function Amp\Promise\wait;
use function Desperado\ServiceBus\Common\writeReflectionPropertyValue;
use Desperado\ServiceBus\Infrastructure\Storage\SQL\AmpPostgreSQL\AmpPostgreSQLAdapter;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Configuration\SagaMetadata;
use Desperado\ServiceBus\Sagas\Contract\SagaClosed;
use Desperado\ServiceBus\Sagas\Exceptions\ExpiredSagaLoaded;
use Desperado\ServiceBus\Sagas\Saga;
use Desperado\ServiceBus\Sagas\SagaStore\SagasStore;
use Desperado\ServiceBus\Sagas\SagaStore\Sql\SQLSagaStore;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;
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

        $this->adapter  = StorageAdapterFactory::create(
            AmpPostgreSQLAdapter::class,
            (string) \getenv('TEST_POSTGRES_DSN')
        );
        $this->store    = new SQLSagaStore($this->adapter);
        $this->provider = new SagaProvider($this->store);
    }

    /**
     * @inheritdoc
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        wait($this->adapter->execute('DROP TABLE IF EXISTS sagas_store'));

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
        $promise = $this->adapter->execute(
            (string) \file_get_contents(__DIR__ . '/../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
        );

        wait($promise);

        $id   = TestSagaId::new(CorrectSaga::class);
        $saga = new CorrectSaga($id);

        wait($this->provider->save($saga, new TestContext()));
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
        $promise = $this->adapter->execute(
            (string) \file_get_contents(__DIR__ . '/../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
        );

        wait($promise);

        $id = TestSagaId::new(CorrectSaga::class);

        $context = new TestContext();

        writeReflectionPropertyValue(
            $this->provider,
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
        $saga = wait($this->provider->start($id, new FirstEmptyCommand(), $context));

        static::assertInstanceOf(Saga::class, $saga);
        static::assertCount(1, $context->messages);

        wait($this->provider->save($saga, $context));

        $loadedSaga = wait($this->provider->obtain($id, $context));

        static::assertInstanceOf(Saga::class, $loadedSaga);
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
        $promise = $this->adapter->execute(
            (string) \file_get_contents(__DIR__ . '/../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
        );

        wait($promise);

        $id = TestSagaId::new(CorrectSaga::class);

        writeReflectionPropertyValue(
            $this->provider,
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

        wait($this->provider->start($id, new FirstEmptyCommand(), new TestContext()));
        wait($this->provider->start($id, new FirstEmptyCommand(), new TestContext()));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\SaveSagaFailed
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveWithoutSchema(): void
    {
        $promise = $this->provider->save(
            new CorrectSaga(TestSagaId::new(CorrectSaga::class)),
            new TestContext()
        );

        wait($promise);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\StartSagaFailed
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function startWithoutSchema(): void
    {
        $sagaId = TestSagaId::new(CorrectSaga::class);

        wait($this->provider->start($sagaId, new FirstEmptyCommand(), new TestContext()));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\SaveSagaFailed
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function saveUnStartedSaga(): void
    {
        $promise = $this->adapter->execute(
            (string) \file_get_contents(__DIR__ . '/../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
        );

        wait($promise);

        $promise = $this->provider->save(
            new CorrectSaga(TestSagaId::new(CorrectSaga::class)),
            new TestContext()
        );

        wait($promise);
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
        $id = TestSagaId::new(CorrectSaga::class);

        $context = new TestContext();

        writeReflectionPropertyValue(
            $this->provider,
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

        wait($this->provider->start($id, new FirstEmptyCommand(), $context));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function loadExpiredSaga(): void
    {
        $promise = $this->adapter->execute(
            (string) \file_get_contents(__DIR__ . '/../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
        );

        wait($promise);

        $id = TestSagaId::new(CorrectSaga::class);

        $context = new TestContext();

        writeReflectionPropertyValue(
            $this->provider,
            'sagaMetaDataCollection',
            [
                CorrectSaga::class => new SagaMetadata(
                    CorrectSaga::class,
                    TestSagaId::class,
                    'requestId',
                    '+1 second'
                )
            ]
        );

        wait($this->provider->start($id, new FirstEmptyCommand(), $context));

        sleep(1);

        try
        {
            wait($this->provider->obtain($id, $context));

            $this->fail('Exception expected');
        }
        catch(\Throwable $throwable)
        {
            static::assertInstanceOf(ExpiredSagaLoaded::class, $throwable);
        }

        /** @var \Desperado\ServiceBus\Sagas\Contract\SagaClosed $latest */
        $latest = \end($context->messages);

        static::assertEquals(true, \is_object($latest));
        static::assertInstanceOf(SagaClosed::class, $latest);
    }
}
