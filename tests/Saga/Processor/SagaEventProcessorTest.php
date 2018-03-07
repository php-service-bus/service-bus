<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga\Processor;

use Desperado\Domain\Uuid;
use Desperado\ServiceBus\Saga\Configuration\AnnotationsSagaConfigurationExtractor;
use Desperado\ServiceBus\Saga\Processor\SagaEventProcessor;
use Desperado\ServiceBus\Saga\Serializer\SagaSerializer;
use Desperado\ServiceBus\Saga\Store\SagaStore;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineConnectionFactory;
use Desperado\ServiceBus\Storage\Doctrine\DoctrineSagaStorage;
use Desperado\ServiceBus\Tests\Saga\LocalDeliveryContext;
use Desperado\ServiceBus\Tests\Saga\Stubs\SagaServiceTestCommand;
use Desperado\ServiceBus\Tests\Saga\Stubs\SagaServiceTestIdentifier;
use Desperado\ServiceBus\Tests\Saga\Stubs\SagaServiceTestSaga;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;


/**
 *
 */
class SagaEventProcessorTest extends TestCase
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
            new AnnotationsSagaConfigurationExtractor()
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
     * @expectedException \Desperado\ServiceBus\Saga\Processor\Exceptions\InvalidSagaIdentifierException
     * @expectedExceptionMessage Event "Desperado\ServiceBus\Tests\Saga\Processor\Negative\NotContainsIdentityKeyEvent" must be
     *                           contains "getIdentifierField" accessor that contains the saga ID
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function identifierFieldNotFound(): void
    {
        $processor = $this->createProcessorInstance(
            Positive\TestEventProcessorSaga::class,
            Positive\TestEventProcessorSagaIdentifier::class,
            'identifierField'
        );

        $processor(Negative\NotContainsIdentityKeyEvent::create(), new LocalDeliveryContext());
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Processor\Exceptions\InvalidSagaIdentifierException
     * @expectedExceptionMessage Identifier class
     *                           "Desperado\ServiceBus\Tests\Saga\Processor\Positive\TestEventProcessorSagaIdentifier" must
     *                           extends "Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function invalidIdentifierObjectType(): void
    {
        $processor = $this->createProcessorInstance(
            Positive\TestEventProcessorSaga::class,
            __CLASS__,
            'identifierField'
        );

        $processor(
            Positive\TestEventProcessorSagaEvent::create(['identifierField' => 'qwerty']),
            new LocalDeliveryContext()
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Processor\Exceptions\InvalidSagaIdentifierException
     * @expectedExceptionMessage Identifier value for event
     *                           "Desperado\ServiceBus\Tests\Saga\Processor\Positive\TestEventProcessorSagaEvent" is empty
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function emptyIdentifierValue(): void
    {
        $processor = $this->createProcessorInstance(
            Positive\TestEventProcessorSaga::class,
            Positive\TestEventProcessorSagaIdentifier::class,
            'identifierField'
        );

        $processor(Positive\TestEventProcessorSagaEvent::create(), new LocalDeliveryContext());
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Saga\Processor\Exceptions\InvalidSagaIdentifierException
     * @expectedExceptionMessage Identifier class "SomeNamespace\SomeClass" not exists
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function nonExistsIdentifierClass(): void
    {
        $processor = $this->createProcessorInstance(
            Positive\TestEventProcessorSaga::class,
            'SomeNamespace\SomeClass',
            'identifierField'
        );

        $processor(
            Positive\TestEventProcessorSagaEvent::create(['identifierField' => 'qwerty']),
            new LocalDeliveryContext()
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successExecution(): void
    {
        $identifier = new SagaServiceTestIdentifier(Uuid::v4(), SagaServiceTestSaga::class);
        $command = new SagaServiceTestCommand();

        $this->sagaProvider->configure(SagaServiceTestSaga::class);

        $this->sagaProvider->start($identifier, $command);
        $this->sagaProvider->flush();

        $context = new LocalDeliveryContext();
        $processor = $this->createProcessorInstance(
            SagaServiceTestSaga::class,
            SagaServiceTestIdentifier::class,
            'identifierField'
        );

        $event = Positive\TestEventProcessorSagaEvent::create(['identifierField' => $identifier->toString()]);

        $processor($event, $context);
    }

    /**
     * @param string $sagaNamespace
     * @param string $identifierNamespace
     * @param string $containingIdentifierProperty
     *
     * @return SagaEventProcessor
     *
     * @throws \Exception
     */
    private function createProcessorInstance(
        string $sagaNamespace,
        string $identifierNamespace,
        string $containingIdentifierProperty
    ): SagaEventProcessor
    {

        return new SagaEventProcessor(
            $sagaNamespace,
            $identifierNamespace,
            $containingIdentifierProperty,
            $this->sagaProvider
        );
    }
}
