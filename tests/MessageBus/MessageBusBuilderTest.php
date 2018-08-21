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

namespace Desperado\ServiceBus\Tests\MessageBus;

use Desperado\ServiceBus\MessageBus\MessageBus;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Sagas\Configuration\AnnotationsBasedSagaConfigurationLoader;
use Desperado\ServiceBus\Services\Configuration\AnnotationsBasedServiceHandlersLoader;
use Desperado\ServiceBus\Tests\MessageBus\Stubs\MessageBusTestingSaga;
use Desperado\ServiceBus\Tests\MessageBus\Stubs\MessageBusTestingSagaStore;
use Desperado\ServiceBus\Tests\MessageBus\Stubs\MessageBusTestingService;
use Desperado\ServiceBus\Tests\MessageBus\Stubs\MessageBusTestingServiceWithDoubleHandlers;
use Desperado\ServiceBus\Tests\MessageBus\Stubs\MessageBusTestingServiceWithoutMessageArgument;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class MessageBusBuilderTest extends TestCase
{
    /**
     * @var MessageBusBuilder
     */
    private $messageBusBuilder;

    /**
     * @var SagaProvider
     */
    private $sagaProvider;

    /**
     * @var TestHandler
     */
    private $testLogHandler;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->testLogHandler = new TestHandler();

        $logger = new Logger(__CLASS__, [$this->testLogHandler]);

        $this->sagaProvider = new SagaProvider(new MessageBusTestingSagaStore());

        $servicesConfigurationLoader = new AnnotationsBasedServiceHandlersLoader();
        $sagasConfigurationLoader    = new AnnotationsBasedSagaConfigurationLoader(
            $this->sagaProvider,
            null,
            $logger
        );

        $this->messageBusBuilder = new MessageBusBuilder(
            $sagasConfigurationLoader,
            $servicesConfigurationLoader,
            $this->sagaProvider,
            $logger
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->messageBusBuilder, $this->testLogHandler);
    }

    /**
     * @test
     *
     * @return void
     */
    public function emptyMessageBus(): void
    {
        $messageBus = $this->messageBusBuilder->compile();

        /** @noinspection UnnecessaryAssertionInspection */
        static::assertInstanceOf(MessageBus::class, $messageBus);

        static::assertNotEmpty($this->testLogHandler->getRecords());
        static::assertCount(1, $this->testLogHandler->getRecords());

        /** @var array $record */
        $record = $this->testLogHandler->getRecords()[0];

        static::assertEquals(
            'The message bus has been successfully configured. "{registeredHandlersCount}" handlers registered',
            $record['message']
        );

        static::assertEquals(['registeredHandlersCount' => 0], $record['context']);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function addCorrectSaga(): void
    {
        $this->messageBusBuilder->addSaga(MessageBusTestingSaga::class);

        $messageBus = $this->messageBusBuilder->compile();

        /** @var array $record */
        $record = $this->testLogHandler->getRecords()[0];

        static::assertEquals(
            'The message bus has been successfully configured. "{registeredHandlersCount}" handlers registered',
            $record['message']
        );

        static::assertEquals(['registeredHandlersCount' => 1], $record['context']);

        /** @var array $metaDataCollection */
        $metaDataCollection = static::readAttribute($this->sagaProvider, 'sagaMetaDataCollection');

        static::assertNotEmpty($metaDataCollection);
        static::assertCount(1, $metaDataCollection);
        static::assertArrayHasKey(MessageBusTestingSaga::class, $metaDataCollection);

        /** @var array $processorsList */
        $processorsList = static::readAttribute($messageBus, 'processorsList');

        static::assertNotEmpty($processorsList);
        static::assertCount(1, $processorsList);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function addCorrectService(): void
    {
        $this->messageBusBuilder->addService(new MessageBusTestingService());

        $messageBus = $this->messageBusBuilder->compile();

        /** @var array $record */
        $record = $this->testLogHandler->getRecords()[0];

        static::assertEquals(
            'The message bus has been successfully configured. "{registeredHandlersCount}" handlers registered',
            $record['message']
        );

        static::assertEquals(['registeredHandlersCount' => 2], $record['context']);

        /** @var array $processorsList */
        $processorsList = static::readAttribute($messageBus, 'processorsList');

        static::assertNotEmpty($processorsList);
        static::assertCount(2, $processorsList);
    }

    /**
     * @test
     * @expectedException  \LogicException
     * @expectedExceptionMessage The handler for the
     *                           "Desperado\ServiceBus\Tests\MessageBus\Stubs\MessageBusTestingCommand" command has
     *                           already been added earlier. You can not add multiple command handlers
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function addDuplicateCommandHandler(): void
    {
        $this->messageBusBuilder->addService(new MessageBusTestingServiceWithDoubleHandlers());
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage In the method of
     *                           "Desperado\ServiceBus\Tests\MessageBus\Stubs\MessageBusTestingServiceWithoutMessageArgument:commandHandler"
     *                           is not found an argument of type
     *                           "Desperado\ServiceBus\Common\Contract\Messages\Message"
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function addServiceHandlerWithoutMessageArgument(): void
    {
        $this->messageBusBuilder->addService(new MessageBusTestingServiceWithoutMessageArgument());
    }
}
