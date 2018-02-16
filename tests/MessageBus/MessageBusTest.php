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

use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\ServiceBus\MessageBus\MessageBus;
use Desperado\ServiceBus\MessageBus\MessageBusTask;
use Desperado\ServiceBus\MessageBus\MessageBusTaskCollection;
use Desperado\ServiceBus\Services\Handlers\EventExecutionParameters;
use Desperado\ServiceBus\Task\CompletedTask;
use Desperado\ServiceBus\Task\Task;
use Desperado\ServiceBus\Tests\Services\Stabs\TestServiceCommand;
use Desperado\ServiceBus\Tests\TestApplicationContext;
use EventLoop\EventLoop;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;

/**
 *
 */
class MessageBusTest extends TestCase
{
    /**
     * @var ExecutionContextInterface
     */
    private $context;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        EventLoop::getLoop()->run();

        $this->context = static::getMockBuilder(TestApplicationContext::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        EventLoop::getLoop()->stop();

        unset($this->context);
    }

    /**
     * @test
     *
     * @return void
     */
    public function successHandle()
    {
        $resultValue = null;

        $closure = \Closure::fromCallable(
            function(AbstractMessage $message, TestApplicationContext $context) use (&$resultValue)
            {
                $resultValue = \get_class($message) . \get_class($context);
            }
        );

        $taskCollection = MessageBusTaskCollection::createFromArray([
            MessageBusTask::create(
                TestServiceCommand::class,
                Task::new($closure, new EventExecutionParameters('default')),
                []
            )
        ]);

        $messageBus = MessageBus::build($taskCollection, new NullLogger());

        $result = $messageBus->handle(new TestServiceCommand(), $this->context);

        $result->then(
            function(array $results) use ($resultValue)
            {
                static::assertCount(1, $results);

                /** @var CompletedTask $completedTask */
                $completedTask = \end($results);

                static::assertEquals(
                    \get_class($completedTask->getMessage()) . \get_class($completedTask->getContext()),
                    $resultValue
                );

                static::assertInstanceOf(FulfilledPromise::class, $completedTask->getTaskResult());
            },
            function(\Throwable $throwable)
            {
                $this->fail($throwable->getMessage());
            }
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function handleWithEmptyHandlers(): void
    {
        $messageBus = MessageBus::build(MessageBusTaskCollection::createEmpty(), new NullLogger());

        $result = $messageBus->handle(new TestServiceCommand(), $this->context);
        $result->then(
            function(array $results)
            {
                static::assertEmpty($results);
            },
            function(\Throwable $throwable)
            {
                $this->fail($throwable->getMessage());
            }
        );
    }

    /**
     * @test
     *
     * @return void
     */
    public function handleWithException(): void
    {
        $closure = \Closure::fromCallable(
            function()
            {
                throw new \LogicException('test message');
            }
        );

        $taskCollection = MessageBusTaskCollection::createFromArray([
            MessageBusTask::create(
                TestServiceCommand::class,
                Task::new($closure, new EventExecutionParameters('default')),
                []
            )
        ]);

        $messageBus = MessageBus::build($taskCollection, new NullLogger());

        $result = $messageBus->handle(new TestServiceCommand(), $this->context);

        $result->then(
            function(array $results)
            {
                /** @var CompletedTask $completedTask */
                $completedTask = \end($results);

                static::assertInstanceOf(RejectedPromise::class, $completedTask->getTaskResult());

                $completedTask
                    ->getTaskResult()
                    ->then(
                        null,
                        function(\Throwable $throwable)
                        {
                            static::assertEquals('test message', $throwable->getMessage());
                            static::assertInstanceOf(\LogicException::class, $throwable);
                        }
                    );
            },
            function(\Throwable $throwable)
            {
                $this->fail($throwable->getMessage());
            }
        );
    }
}
