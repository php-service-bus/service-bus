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

use Desperado\Domain\Uuid;
use Desperado\ServiceBus\Saga\Configuration\AnnotationsSagaConfigurationExtractor;
use Desperado\ServiceBus\Saga\Metadata\SagaListener;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Saga\Serializer\SagaSerializer;
use Desperado\ServiceBus\Saga\Store\SagaStore;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineConnectionFactory;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineSagaStorage;
use Desperado\ServiceBus\Tests\Saga\Stubs\SagaServiceTestCommand;
use Desperado\ServiceBus\Tests\Saga\Stubs\SagaServiceTestEvent;
use Desperado\ServiceBus\Tests\Saga\Stubs\SagaServiceTestIdentifier;
use Desperado\ServiceBus\Tests\Saga\Stubs\SagaServiceTestSaga;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 *
 */
class SagaProviderTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var DoctrineSagaStorage
     */
    private $storage;

    /**
     * @var SagaSerializer
     */
    private $serializer;

    /**
     * @var SagaStore
     */
    private $store;

    /**
     * @var SagaProvider
     */
    private $sagaProvider;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DoctrineConnectionFactory::create('sqlite:///:memory:');
        $this->storage = new DoctrineSagaStorage($this->connection);
        $this->serializer = new SagaSerializer();
        $this->store = new SagaStore($this->storage, $this->serializer);
        $this->sagaProvider = new SagaProvider(
            $this->store,
            new AnnotationsSagaConfigurationExtractor(),
            new NullLogger()
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->connection->close();

        unset($this->connection, $this->storage, $this->serializer, $this->store, $this->sagaProvider);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Exceptions\SagaClassNotFoundException
     * @expectedExceptionMessage Saga class "SagaNamespace" not found
     *
     * @return void
     */
    public function wrongSagaNamespace(): void
    {
        $this->sagaProvider->configure('SagaNamespace');
    }

    /**
     * @test
     *
     * @return void
     */
    public function configure(): void
    {
        $this->sagaProvider->configure(SagaServiceTestSaga::class);

        $messageHandlers = $this->sagaProvider->getSagaListeners(SagaServiceTestSaga::class);

        static::assertCount(1, $messageHandlers);

        /** @var SagaListener $sagaEventListener */
        $sagaEventListener = \end($messageHandlers);

        static::assertEquals(SagaServiceTestEvent::class, $sagaEventListener->getEventNamespace());
        static::assertInstanceOf(\Closure::class, $sagaEventListener->getHandler());
    }

    /**
     * @test
     *
     * @return void
     */
    public function startSaga(): void
    {
        $identifier = new SagaServiceTestIdentifier(Uuid::v4(), SagaServiceTestSaga::class);
        $command = new SagaServiceTestCommand();

        $this->sagaProvider->configure(SagaServiceTestSaga::class);

        $saga = $this->sagaProvider->start($identifier, $command);

        static::assertInstanceOf(SagaServiceTestSaga::class, $saga);

        $this->sagaProvider->flush();

        $loadedSaga = $this->sagaProvider->obtain($identifier);

        static::assertInstanceOf(SagaServiceTestSaga::class, $loadedSaga);
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Exceptions\SagaNotConfiguredException
     *
     * @return void
     */
    public function startNotConfiguredSaga(): void
    {
        $this->sagaProvider->start(
            new SagaServiceTestIdentifier(Uuid::v4(), SagaServiceTestSaga::class),
            new SagaServiceTestCommand()
        );
    }
}
