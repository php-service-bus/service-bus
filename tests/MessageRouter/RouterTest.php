<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\MessageRouter;

use PHPUnit\Framework\TestCase;
use ServiceBus\MessageExecutor\DefaultMessageExecutor;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use ServiceBus\MessageRouter\Router;
use ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent;

/**
 *
 */
final class RouterTest extends TestCase
{
    /**
     * @test
     * @expectedException \ServiceBus\MessageRouter\Exceptions\InvalidEventClassSpecified
     * @expectedExceptionMessage The event class is not specified, or does not exist
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function emptyEventClass(): void
    {
        (new Router())->registerListener(
            '',
            new DefaultMessageExecutor(
                \Closure::fromCallable(
                    function()
                    {

                    }
                ),
                new \SplObjectStorage(),
                DefaultHandlerOptions::createForEventListener(),
                []
            )
        );
    }

    /**
     * @test
     * @expectedException \ServiceBus\MessageRouter\Exceptions\InvalidEventClassSpecified
     * @expectedExceptionMessage The event class is not specified, or does not exist
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function unExistsEventClass(): void
    {
        (new Router())->registerListener(
            'SomeEventClass',
            new DefaultMessageExecutor(
                \Closure::fromCallable(
                    function()
                    {

                    }
                ),
                new \SplObjectStorage(),
                DefaultHandlerOptions::createForEventListener(),
                []
            )
        );
    }

    /**
     * @test
     * @expectedException \ServiceBus\MessageRouter\Exceptions\InvalidCommandClassSpecified
     * @expectedExceptionMessage The command class is not specified, or does not exist
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function emptyCommandClass(): void
    {
        (new Router())->registerHandler(
            '',
            new DefaultMessageExecutor(
                \Closure::fromCallable(
                    function()
                    {

                    }
                ),
                new \SplObjectStorage(),
                DefaultHandlerOptions::createForCommandHandler(),
                []
            )
        );
    }

    /**
     * @test
     * @expectedException \ServiceBus\MessageRouter\Exceptions\InvalidCommandClassSpecified
     * @expectedExceptionMessage The command class is not specified, or does not exist
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function unExistsCommandClass(): void
    {
        (new Router())->registerHandler(
            'SomeCommandClass',
            new DefaultMessageExecutor(
                \Closure::fromCallable(
                    function()
                    {

                    }
                ),
                new \SplObjectStorage(),
                DefaultHandlerOptions::createForCommandHandler(),
                []
            )
        );
    }

    /**
     * @test
     * @expectedException \ServiceBus\MessageRouter\Exceptions\MultipleCommandHandlersNotAllowed
     * @expectedExceptionMessage A handler has already been registered for the
     *                           "ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand" command
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function duplicateCommand(): void
    {
        $router = new Router();

        $handler = new DefaultMessageExecutor(
            \Closure::fromCallable(
                function()
                {

                }
            ),
            new \SplObjectStorage(),
            DefaultHandlerOptions::createForCommandHandler(),
            []
        );

        $router->registerHandler(FirstEmptyCommand::class, $handler);
        $router->registerHandler(FirstEmptyCommand::class, $handler);
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function successRegister(): void
    {
        $handler = new DefaultMessageExecutor(
            \Closure::fromCallable(
                function()
                {

                }
            ),
            new \SplObjectStorage(),
            DefaultHandlerOptions::createForCommandHandler(),
            []
        );

        $router = new Router();

        static::assertCount(0, $router->match(new FirstEmptyCommand));
        static::assertCount(0, $router->match(new FirstEmptyEvent()));

        $router->registerHandler(FirstEmptyCommand::class, $handler);

        $router->registerListener(FirstEmptyEvent::class, $handler);
        $router->registerListener(FirstEmptyEvent::class, $handler);

        static::assertCount(3, $router);
        static::assertCount(1, $router->match(new FirstEmptyCommand));
        static::assertCount(2, $router->match(new FirstEmptyEvent));
    }
}
