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
use Desperado\ServiceBus\Sagas\Exceptions\InvalidIdentifier;
use Desperado\ServiceBus\Sagas\SagaStore\Sql\SQLSagaStore;
use Desperado\ServiceBus\Storage\StorageAdapterFactory;
use Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\CorrectTestProcessorSaga;
use Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\IncorrectSagaId;
use Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\SimpleReceivedEvent;
use Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\StartSagaCommand;
use Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\SuccessResponseEvent;
use Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\TestProcessorContext;
use Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\TestProcessorSagaId;
use Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\TestSagaStore;
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
                . '"Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\SimpleReceivedEvent"'
            );

            $processor = new SagaEventListenerProcessor(
                SagaListenerOptions::withGlobalOptions(
                    new SagaMetadata(
                        CorrectTestProcessorSaga::class,
                        TestProcessorSagaId::class,
                        '',
                        '+1 year'
                    )
                ),
                new SagaProvider(new TestSagaStore())
            );

            yield $processor->execute(new SimpleReceivedEvent(), new TestProcessorContext());
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
                'The value of the "requestId" property of the '
                . '"Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\SimpleReceivedEvent" event can not '
                . 'be empty, since it is the saga id'
            );

            $processor = new SagaEventListenerProcessor(
                SagaListenerOptions::withGlobalOptions(
                    new SagaMetadata(
                        CorrectTestProcessorSaga::class,
                        TestProcessorSagaId::class,
                        'requestId',
                        '+1 year'
                    )
                ),
                new SagaProvider(new TestSagaStore())
            );

            yield $processor->execute(new SimpleReceivedEvent(), new TestProcessorContext());
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
            $self->expectException(InvalidIdentifier::class);
            $self->expectExceptionMessage(
                'Saga identifier mus be type of "Desperado\ServiceBus\Sagas\SagaId". '
                . '"Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\IncorrectSagaId" type specified'
            );

            $processor = new SagaEventListenerProcessor(
                SagaListenerOptions::withGlobalOptions(
                    new SagaMetadata(
                        CorrectTestProcessorSaga::class,
                        IncorrectSagaId::class,
                        'requestId',
                        '+1 year'
                    )
                ),
                new SagaProvider(new TestSagaStore())
            );

            yield $processor->execute(new SimpleReceivedEvent('root'), new TestProcessorContext());
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
                . 'specified in the saga "Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs\CorrectTestProcessorSaga" '
                . 'not found'
            );

            /** @noinspection PhpUndefinedClassInspection */
            $processor = new SagaEventListenerProcessor(
                SagaListenerOptions::withGlobalOptions(
                    new SagaMetadata(
                        CorrectTestProcessorSaga::class,
                        SomeUnExistsClass::class,
                        'requestId',
                        '+1 year'
                    )
                ),
                new SagaProvider(new TestSagaStore())
            );

            yield $processor->execute(new SimpleReceivedEvent('root'), new TestProcessorContext());
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
            $sagaStoreMock = $self->getMockBuilder(TestSagaStore::class)
                ->setMethods(['load'])
                ->getMock();

            $sagaStoreMock
                ->method('load')
                ->willReturn(new Success(null));

            $testLogHandler = new TestHandler();
            $logger         = new Logger('testing', [$testLogHandler]);

            /** @var TestSagaStore $sagaStoreMock */

            $processor = new SagaEventListenerProcessor(
                SagaListenerOptions::withGlobalOptions(
                    new SagaMetadata(
                        CorrectTestProcessorSaga::class,
                        TestProcessorSagaId::class,
                        'requestId',
                        '+1 year'
                    )
                ),
                new SagaProvider($sagaStoreMock),
                $logger
            );

            yield $processor->execute(
                new SimpleReceivedEvent(uuid()), new TestProcessorContext()
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

            $context = new TestProcessorContext();

            $id = TestProcessorSagaId::new(CorrectTestProcessorSaga::class);

            $sagaProvider = new SagaProvider(new SQLSagaStore($adapter));

            writeReflectionPropertyValue(
                $sagaProvider,
                'sagaMetaDataCollection',
                [
                    CorrectTestProcessorSaga::class => new SagaMetadata(
                        CorrectTestProcessorSaga::class,
                        TestProcessorSagaId::class,
                        'requestId',
                        '+1 year'
                    )
                ]
            );

            /** @var CorrectTestProcessorSaga $saga */
            $saga = yield $sagaProvider->start($id, new StartSagaCommand(), $context);

            yield $sagaProvider->save($saga, $context);

            /** @var TestSagaStore $sagaStoreMock */

            $testLogHandler = new TestHandler();
            $logger         = new Logger('testing', [$testLogHandler]);

            $processor = new SagaEventListenerProcessor(
                SagaListenerOptions::withGlobalOptions(
                    new SagaMetadata(
                        CorrectTestProcessorSaga::class,
                        TestProcessorSagaId::class,
                        'requestId',
                        '+1 year'
                    )
                ),
                $sagaProvider,
                $logger
            );

            yield $processor->execute(new SimpleReceivedEvent((string) $id), $context);

            static::assertFalse($testLogHandler->hasRecords(200));

            /** @var array $messages */
            $messages = readReflectionPropertyValue($context, 'messages');

            static::assertInternalType('array', $messages);
            static::assertNotEmpty($messages);

            /** @var SuccessResponseEvent $responseEvent */
            $responseEvent = $messages[0];

            static::assertEquals((string) $id, $responseEvent->requestId());
        };

        wait(new Coroutine($handler()));
    }
}
