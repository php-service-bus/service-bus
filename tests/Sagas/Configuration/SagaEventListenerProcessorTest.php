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

namespace Desperado\ServiceBus\Tests\Sagas\Configuration;

use function Amp\Promise\wait;
use Amp\Success;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use function Desperado\ServiceBus\Common\uuid;
use function Desperado\ServiceBus\Common\writeReflectionPropertyValue;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Configuration\SagaListenerOptions;
use Desperado\ServiceBus\Sagas\Configuration\SagaMetadata;
use Desperado\ServiceBus\Sagas\SagaStore\Sql\SQLSagaStore;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Tests\Stubs\Context\TestContext;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEventWithKey;
use Desperado\ServiceBus\Tests\Stubs\Messages\SecondEventWithKey;
use Desperado\ServiceBus\Tests\Stubs\Sagas\CorrectSaga;
use Desperado\ServiceBus\Tests\Stubs\Sagas\IncorrectSagaId;
use Desperado\ServiceBus\Tests\Stubs\Sagas\SagasStoreStub;
use Desperado\ServiceBus\Tests\Stubs\Sagas\TestSagaId;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Constraint\IsType;
use PHPUnit\Framework\TestCase;
use Desperado\ServiceBus\Sagas\Configuration\SagaEventListenerProcessor;

/**
 *
 */
final class SagaEventListenerProcessorTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Configuration\Exceptions\IncorrectIdentifierFieldSpecified
     * @expectedExceptionMessage A property that contains an identifier ("") was not found in class
     *                           "Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent"
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function withWrongContainingIdentifierProperty(): void
    {
        $processor = new SagaEventListenerProcessor(
            SagaListenerOptions::withGlobalOptions(
                new SagaMetadata(
                    CorrectSaga::class,
                    TestSagaId::class,
                    '',
                    '+1 year'
                )
            ),
            new SagaProvider(new SagasStoreStub())
        );

        wait($processor->execute(new FirstEmptyEvent(), new TestContext()));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Configuration\Exceptions\IncorrectIdentifierFieldSpecified
     * @expectedExceptionMessage The value of the "key" property of the
     *                           "Desperado\ServiceBus\Tests\Stubs\Messages\FirstEventWithKey" event can not be empty,
     *                           since it is the saga id
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function withEmptyContainingIdentifierProperty(): void
    {
        $processor = new SagaEventListenerProcessor(
            SagaListenerOptions::withGlobalOptions(
                new SagaMetadata(
                    CorrectSaga::class,
                    TestSagaId::class,
                    'key',
                    '+1 year'
                )
            ),
            new SagaProvider(new SagasStoreStub())
        );

        wait($processor->execute(new FirstEventWithKey(''), new TestContext()));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     * @expectedExceptionMessage Saga identifier mus be type of "Desperado\ServiceBus\Sagas\SagaId".
     *                           "Desperado\ServiceBus\Tests\Stubs\Sagas\IncorrectSagaId" type specified
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function withWrongSagaIdType(): void
    {
        $processor = new SagaEventListenerProcessor(
            SagaListenerOptions::withGlobalOptions(
                new SagaMetadata(
                    CorrectSaga::class,
                    IncorrectSagaId::class,
                    'key',
                    '+1 year'
                )
            ),
            new SagaProvider(new SagasStoreStub())
        );

        wait($processor->execute(new FirstEventWithKey('root'), new TestContext()));
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\Sagas\Configuration\Exceptions\IdentifierClassNotFound
     * @expectedExceptionMessage Identifier class "Desperado\ServiceBus\Tests\Sagas\Configuration\SomeUnExistsClass"
     *                           specified in the saga "Desperado\ServiceBus\Tests\Stubs\Sagas\\CorrectSaga" not found
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function withUnExistsSagaId(): void
    {
        /** @noinspection PhpUndefinedClassInspection */
        $processor = new SagaEventListenerProcessor(
            SagaListenerOptions::withGlobalOptions(
                new SagaMetadata(
                    CorrectSaga::class,
                    SomeUnExistsClass::class,
                    'key',
                    '+1 year'
                )
            ),
            new SagaProvider(new SagasStoreStub())
        );

        wait($processor->execute(new FirstEventWithKey(uuid()), new TestContext()));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function executeWithUnExistsSaga(): \Generator
    {
        $sagaStoreMock = $this->getMockBuilder(SagasStoreStub::class)
            ->setMethods(['load'])
            ->getMock();

        $sagaStoreMock
            ->method('load')
            ->willReturn(yield new Success(null));

        /** @var SagasStoreStub $sagaStoreMock */

        $processor = new SagaEventListenerProcessor(
            SagaListenerOptions::withGlobalOptions(
                new SagaMetadata(
                    CorrectSaga::class,
                    TestSagaId::class,
                    'key',
                    '+1 year'
                )
            ),
            new SagaProvider($sagaStoreMock)
        );

        $context        = new TestContext();
        $testLogHandler = $context->testLogHandler();

        wait($processor->execute(new FirstEventWithKey(uuid()), $context));

        static::assertTrue($testLogHandler->hasRecords(200));
        static::assertCount(1, $testLogHandler->getRecords());

        /** @var array $record */
        $record = $testLogHandler->getRecords()[0];

        static::assertEquals(
            'Saga with identifier "{sagaId}:{sagaClass}" not found',
            $record['message']
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successTransition(): void
    {
        $adapter = StorageAdapterFactory::inMemory();

        wait(
            $adapter->execute(
                \file_get_contents(__DIR__ . '/../../../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
            )
        );

        $context = new TestContext();

        $id = TestSagaId::new(CorrectSaga::class);

        $sagaProvider = new SagaProvider(new SQLSagaStore($adapter));

        writeReflectionPropertyValue(
            $sagaProvider,
            'sagaMetaDataCollection',
            [
                CorrectSaga::class => new SagaMetadata(
                    CorrectSaga::class,
                    TestSagaId::class,
                    'key',
                    '+1 year'
                )
            ]
        );

        /** @var CorrectSaga $saga */
        $saga = wait($sagaProvider->start($id, new FirstEmptyCommand(), $context));

        wait($sagaProvider->save($saga, $context));

        /** @var SagasStoreStub $sagaStoreMock */

        $testLogHandler = new TestHandler();

        $processor = new SagaEventListenerProcessor(
            SagaListenerOptions::withGlobalOptions(
                new SagaMetadata(
                    CorrectSaga::class,
                    TestSagaId::class,
                    'key',
                    '+1 year'
                )
            ),
            $sagaProvider
        );

        wait($processor->execute(new FirstEventWithKey((string) $id), $context));

        static::assertFalse($testLogHandler->hasRecords(200));

        /** @var array $messages */
        $messages = readReflectionPropertyValue($context, 'messages');

        static::assertThat($messages, new IsType('array'));
        static::assertNotEmpty($messages);

        /** @var SecondEventWithKey $responseEvent */
        $responseEvent = $messages[1];

        static::assertEquals((string) $id, $responseEvent->key());
    }
}
