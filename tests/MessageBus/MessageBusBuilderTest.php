<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\MessageBus;

use Desperado\Infrastructure\Bridge\AnnotationsReader\AnnotationsReaderInterface;
use Desperado\Infrastructure\Bridge\AnnotationsReader\DoctrineAnnotationsReader;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\MessageBus\MessageBusTask;
use Desperado\ServiceBus\MessageBus\MessageBusTaskCollection;
use Desperado\ServiceBus\Services\AnnotationsExtractor;
use Desperado\ServiceBus\Services\AutowiringServiceLocator;
use Desperado\ServiceBus\Task\Behaviors\ValidationBehavior;
use Desperado\ServiceBus\Task\Interceptors\ValidateInterceptor;
use Desperado\ServiceBus\Task\Task;
use Desperado\ServiceBus\Task\TaskInterface;
use Desperado\ServiceBus\Tests\Services\Stabs\CorrectServiceWithHandlers;
use Desperado\ServiceBus\Tests\Services\Stabs\TestServiceCommand;
use Desperado\ServiceBus\Tests\Services\Stabs\TestServiceEvent;
use Desperado\ServiceBus\Tests\TestContainer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 *
 */
class MessageBusBuilderTest extends TestCase
{
    /**
     * @var TestContainer
     */
    private $container;

    /**
     * @var AnnotationsReaderInterface
     */
    private $annotationsReader;

    /**
     * @var AutowiringServiceLocator
     */
    private $autowiringServiceLocator;

    /**
     * @var AnnotationsExtractor
     */
    private $serviceHandlersExtractor;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;


    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new TestContainer();
        $this->eventDispatcher = new EventDispatcher();
        $this->autowiringServiceLocator = new AutowiringServiceLocator($this->container, []);
        $this->annotationsReader = new DoctrineAnnotationsReader();
        $this->serviceHandlersExtractor = new AnnotationsExtractor(
            $this->annotationsReader,
            $this->autowiringServiceLocator,
            new NullLogger()
        );
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset(
            $this->annotationsReader,
            $this->autowiringServiceLocator,
            $this->serviceHandlersExtractor,
            $this->eventDispatcher,
            $this->container
        );
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    public function successBuild(): void
    {
        $logger = new NullLogger();
        $builder = new MessageBusBuilder($this->serviceHandlersExtractor, $this->eventDispatcher, $logger);
        $builder->applyService(new CorrectServiceWithHandlers());

        static::assertFalse($builder->isCompiled());

        $messageBus = $builder->build();

        static::assertTrue($builder->isCompiled());

        /** @var MessageBusTaskCollection $messageBusTaskCollection */
        $messageBusTaskCollection = static::readAttribute($messageBus, 'taskCollection');

        static::assertCount(4, $messageBusTaskCollection);
        static::assertEquals($logger, static::readAttribute($messageBus, 'logger'));

        $expectedMessageNamespaces = [
            TestServiceCommand::class => 1,
            TestServiceEvent::class   => 3
        ];

        foreach($expectedMessageNamespaces as $messageNamespace => $expectedHandlersCount)
        {
            $tasks = $messageBusTaskCollection->mapByMessageNamespace($messageNamespace);

            static::assertNotEmpty($tasks);
            static::assertCount($expectedHandlersCount, $tasks);

            foreach($tasks as $task)
            {
                static::assertInstanceOf(MessageBusTask::class, $task);
                static::assertEquals($messageNamespace, $task->getMessageNamespace());
                static::assertEmpty($task->getAutowiringServices());
                static::assertInstanceOf(TaskInterface::class, $task->getTask());
                static::assertInstanceOf(Task::class, $task->getTask());
            }
        }
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\MessageBus\Exceptions\MessageBusAlreadyCreatedException
     * @expectedExceptionMessage Message bus already created
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    public function configureClosedBuilder(): void
    {
        $logger = new NullLogger();
        $builder = new MessageBusBuilder($this->serviceHandlersExtractor, $this->eventDispatcher, $logger);
        $builder->build();

        $builder->applyService(new CorrectServiceWithHandlers());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    public function configureBehavior(): void
    {
        $logger = new NullLogger();
        $builder = new MessageBusBuilder($this->serviceHandlersExtractor, $this->eventDispatcher, $logger);

        $builder->applyService(new CorrectServiceWithHandlers());
        $builder->pushBehavior(ValidationBehavior::create());

        $messageBus = $builder->build();

        /** @var MessageBusTaskCollection $taskCollection */
        $taskCollection = static::readAttribute($messageBus, 'taskCollection');
        $messageHandlers = $taskCollection->mapByMessageNamespace(TestServiceCommand::class);

        foreach($messageHandlers as $messageHandler)
        {
            /** @var MessageBusTask $messageHandler */

            static::assertInstanceOf(ValidateInterceptor::class, $messageHandler->getTask());
        }
    }
}
