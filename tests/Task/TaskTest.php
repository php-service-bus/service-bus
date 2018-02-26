<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Task;

use Desperado\ServiceBus\Services\Handlers\CommandExecutionParameters;
use Desperado\ServiceBus\Task\Task;
use Desperado\ServiceBus\Tests\Saga\LocalDeliveryContext;
use Desperado\ServiceBus\Tests\TestApplicationContext;
use PHPUnit\Framework\TestCase;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

/**
 *
 */
class TaskTest extends TestCase
{
    /**
     * @var LocalDeliveryContext
     */
    private $context;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->context = new LocalDeliveryContext();
    }

    /**
     * @inheritdoc
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     *
     * @return void
     */
    public function successInvoke(): void
    {
        $field = null;

        $closure = \Closure::fromCallable(
            function() use (&$field)
            {
                $field = 'qwerty';

                return new FulfilledPromise();
            }
        );

        $options = new CommandExecutionParameters('channel');
        $task = Task::new($closure, $options);

        $result = $task(new TaskTestCommand(), $this->context);

        static::assertEquals('qwerty', $field);
        static::assertEquals($options, $task->getOptions());
        static::assertNotNull($result);
        static::assertInstanceOf(PromiseInterface::class, $result);
    }
}
