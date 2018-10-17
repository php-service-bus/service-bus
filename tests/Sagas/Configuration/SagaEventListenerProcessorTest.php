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

use Amp\Coroutine;
use function Amp\Promise\wait;
use Amp\Success;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use function Desperado\ServiceBus\Common\uuid;
use function Desperado\ServiceBus\Common\writeReflectionPropertyValue;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Configuration\Exceptions\IdentifierClassNotFound;
use Desperado\ServiceBus\Sagas\Configuration\Exceptions\IncorrectIdentifierFieldSpecified;
use Desperado\ServiceBus\Sagas\Configuration\SagaListenerOptions;
use Desperado\ServiceBus\Sagas\Configuration\SagaMetadata;
use Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier;
use Desperado\ServiceBus\Sagas\SagaStore\Sql\SQLSagaStore;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
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
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use Desperado\ServiceBus\Sagas\Configuration\SagaEventListenerProcessor;

/**
 *
 */
final class SagaEventListenerProcessorTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function withWrongContainingIdentifierProperty(): void
    {
        $handler = static function(SagaEventListenerProcessorTest $self): \Generator
        {
            $self->expectException(IncorrectIdentifierFieldSpecified::class);
            $self->expectExceptionMessage(
                'A property that contains an identifier ("") was not found in class '
                . '"Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent"'
            );

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

            yield $processor->execute(new FirstEmptyEvent(), new TestContext());
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
    public function withEmptyContainingIdentifierProperty(): void
    {
        $handler = static function(SagaEventListenerProcessorTest $self): \Generator
        {
            $self->expectException(IncorrectIdentifierFieldSpecified::class);
            $self->expectExceptionMessage(
                'The value of the "key" property of the '
                . '"Desperado\ServiceBus\Tests\Stubs\Messages\FirstEventWithKey" event can not '
                . 'be empty, since it is the saga id'
            );

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

            yield $processor->execute(new FirstEventWithKey(''), new TestContext());
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
    public function withWrongSagaIdType(): void
    {
        $handler = static function(SagaEventListenerProcessorTest $self): \Generator
        {
            $self->expectException(InvalidSagaIdentifier::class);
            $self->expectExceptionMessage(
                'Saga identifier mus be type of "Desperado\ServiceBus\Sagas\SagaId". '
                . '"Desperado\ServiceBus\Tests\Stubs\Sagas\IncorrectSagaId" type specified'
            );

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

            yield $processor->execute(new FirstEventWithKey('root'), new TestContext());
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
    public function withUnExistsSagaId(): void
    {
        $handler = static function(SagaEventListenerProcessorTest $self): \Generator
        {
            $self->expectException(IdentifierClassNotFound::class);
            $self->expectExceptionMessage(
                'Identifier class "Desperado\ServiceBus\Tests\Sagas\Configuration\SomeUnExistsClass" '
                . 'specified in the saga "Desperado\ServiceBus\Tests\Stubs\Sagas\\CorrectSaga" '
                . 'not found'
            );

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

            yield $processor->execute(new FirstEventWithKey(uuid()), new TestContext());
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
    public function executeWithUnExistsSaga(): void
    {
        $handler = static function(SagaEventListenerProcessorTest $self): \Generator
        {
            $sagaStoreMock = $self->getMockBuilder(SagasStoreStub::class)
                ->setMethods(['load'])
                ->getMock();

            $sagaStoreMock
                ->method('load')
                ->willReturn(new Success(null));

            $testLogHandler = new TestHandler();
            $logger         = new Logger('testing', [$testLogHandler]);

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
                new SagaProvider($sagaStoreMock),
                $logger
            );

            yield $processor->execute(
                new FirstEventWithKey(uuid()), new TestContext()
            );

            static::assertTrue($testLogHandler->hasRecords(200));
            static::assertCount(1, $testLogHandler->getRecords());

            /** @var array $record */
            $record = $testLogHandler->getRecords()[0];

            static::assertEquals(
                'Saga with identifier "{sagaId}:{sagaClass}" not found',
                $record['message']
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
    public function successTransition(): void
    {
        $handler = static function(): \Generator
        {
            $adapter = StorageAdapterFactory::inMemory();

            yield $adapter->execute(
                \file_get_contents(__DIR__ . '/../../../src/Sagas/SagaStore/Sql/schema/sagas_store.sql')
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
            $saga = yield $sagaProvider->start($id, new FirstEmptyCommand(), $context);

            yield $sagaProvider->save($saga, $context);

            /** @var SagasStoreStub $sagaStoreMock */

            $testLogHandler = new TestHandler();
            $logger         = new Logger('testing', [$testLogHandler]);

            $processor = new SagaEventListenerProcessor(
                SagaListenerOptions::withGlobalOptions(
                    new SagaMetadata(
                        CorrectSaga::class,
                        TestSagaId::class,
                        'key',
                        '+1 year'
                    )
                ),
                $sagaProvider,
                $logger
            );

            yield $processor->execute(new FirstEventWithKey((string) $id), $context);

            static::assertFalse($testLogHandler->hasRecords(200));

            /** @var array $messages */
            $messages = readReflectionPropertyValue($context, 'messages');

            static::assertInternalType('array', $messages);
            static::assertNotEmpty($messages);

            /** @var SecondEventWithKey $responseEvent */
            $responseEvent = $messages[1];

            static::assertEquals((string) $id, $responseEvent->key());
        };

        wait(new Coroutine($handler()));
    }
}
