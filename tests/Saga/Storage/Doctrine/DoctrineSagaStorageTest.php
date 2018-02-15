<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Storage\Doctrine;

use Desperado\ServiceBus\Saga\Metadata\SagaMetadata;
use Desperado\ServiceBus\Saga\Serializer\SagaSerializer;
use Desperado\ServiceBus\Saga\Storage\StoredSaga;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineConnectionFactory;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineSagaStorage;
use Desperado\ServiceBus\Tests\Saga\TestSaga;
use Desperado\ServiceBus\Tests\Saga\TestSagaIdentifier;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class DoctrineSagaStorageTest extends TestCase
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
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = DoctrineConnectionFactory::create('sqlite:///:memory:');
        $this->storage = new DoctrineSagaStorage($this->connection);
        $this->serializer = new SagaSerializer();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->connection->close();

        unset($this->connection, $this->storage, $this->serializer);
    }

    /**
     * @test
     *
     * @return void
     */
    public function sagaFlow(): void
    {
        $identifier = new TestSagaIdentifier('c30b5651-b702-4d6f-b1e1-14fd9812f1ca', TestSaga::class);
        $sagaMetadata = SagaMetadata::create(
            TestSaga::class,
            '+1 day',
            TestSagaIdentifier::class,
            'requestId'
        );

        $saga = new TestSaga($identifier, $sagaMetadata);

        $sagaPayload = $this->serializer->serialize($saga);

        $storedSaga = StoredSaga::create(
            $identifier,
            $sagaPayload,
            $saga->getState()->getStatusCode(),
            $saga->getState()->getCreatedAt(),
            $saga->getState()->getClosedAt()
        );

        $this->storage->save($storedSaga);

        $loadedStoredSaga = $this->storage->load($identifier);

        static::assertNotNull($loadedStoredSaga);

        static::assertEquals($identifier->toString(), $loadedStoredSaga->getIdentifier());
        static::assertEquals($identifier->getIdentityClassNamespace(), $loadedStoredSaga->getIdentifierNamespace());
        static::assertEquals($identifier->getSagaNamespace(), $loadedStoredSaga->getSagaNamespace());

        static::assertEquals($sagaPayload, $loadedStoredSaga->getPayload());

        $this->storage->remove($identifier);

        static::assertNull($this->storage->load($identifier));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationException
     *
     * @return void
     */
    public function saveDuplicateSaga(): void
    {
        $identifier = new TestSagaIdentifier('c30b5651-b702-4d6f-b1e1-14fd9812f1ca', TestSaga::class);
        $sagaMetadata = SagaMetadata::create(
            TestSaga::class,
            '+1 day',
            TestSagaIdentifier::class,
            'requestId'
        );

        $saga = new TestSaga($identifier, $sagaMetadata);
        $saga->closeCommand('test reason');

        $sagaPayload = $this->serializer->serialize($saga);

        $storedSaga = StoredSaga::create(
            $identifier,
            $sagaPayload,
            $saga->getState()->getStatusCode(),
            $saga->getState()->getCreatedAt(),
            $saga->getState()->getClosedAt()
        );

        $this->storage->save($storedSaga);

        $newSaga = clone $storedSaga;

        $this->storage->save($newSaga);
    }

    /**
     * @test
     *
     * @return void
     */
    public function updateSaga(): void
    {
        $identifier = new TestSagaIdentifier('c30b5651-b702-4d6f-b1e1-14fd9812f1ca', TestSaga::class);
        $sagaMetadata = SagaMetadata::create(
            TestSaga::class,
            '+1 day',
            TestSagaIdentifier::class,
            'requestId'
        );

        $saga = new TestSaga($identifier, $sagaMetadata);

        $storedSaga = StoredSaga::create(
            $identifier,
            $this->serializer->serialize($saga),
            $saga->getState()->getStatusCode(),
            $saga->getState()->getCreatedAt(),
            $saga->getState()->getClosedAt()
        );

        $this->storage->save($storedSaga);

        $saga->closeCommand('test reason');

        $storedSaga = StoredSaga::create(
            $identifier,
            $this->serializer->serialize($saga),
            $saga->getState()->getStatusCode(),
            $saga->getState()->getCreatedAt(),
            $saga->getState()->getClosedAt()
        );

        $this->storage->update($storedSaga);

        $loadedSaga = $this->storage->load($identifier);

        static::assertTrue($loadedSaga->isClosed());
    }
}
