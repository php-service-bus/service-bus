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

namespace Desperado\ServiceBus\Tests\MessageRouter;

use Desperado\ServiceBus\MessageExecutor\DefaultMessageExecutor;
use Desperado\ServiceBus\MessageHandlers\HandlerArgumentCollection;
use Desperado\ServiceBus\MessageHandlers\HandlerOptions;
use Desperado\ServiceBus\MessageRouter\Router;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class RouterTest extends TestCase
{
    /**
     * @test
     * @expectedException \Desperado\ServiceBus\MessageRouter\Exceptions\InvalidEventClassSpecified
     * @expectedExceptionMessage The event class is not specified, or does not exist
     *
     * @return void
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
                new HandlerArgumentCollection(),
                new HandlerOptions(),
                []
            )
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\MessageRouter\Exceptions\InvalidEventClassSpecified
     * @expectedExceptionMessage The event class is not specified, or does not exist
     *
     * @return void
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
                new HandlerArgumentCollection(),
                new HandlerOptions(),
                []
            )
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\MessageRouter\Exceptions\InvalidCommandClassSpecified
     * @expectedExceptionMessage The command class is not specified, or does not exist
     *
     * @return void
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
                new HandlerArgumentCollection(),
                new HandlerOptions(),
                []
            )
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\MessageRouter\Exceptions\InvalidCommandClassSpecified
     * @expectedExceptionMessage The command class is not specified, or does not exist
     *
     * @return void
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
                new HandlerArgumentCollection(),
                new HandlerOptions(),
                []
            )
        );
    }

    /**
     * @test
     * @expectedException \Desperado\ServiceBus\MessageRouter\Exceptions\MultipleCommandHandlersNotAllowed
     * @expectedExceptionMessage A handler has already been registered for the
     *                           "Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand" command
     *
     * @return void
     */
    public function duplicateCommand(): void
    {
        $router  = new Router();

        $handler = new DefaultMessageExecutor(
            \Closure::fromCallable(
                function()
                {

                }
            ),
            new HandlerArgumentCollection(),
            new HandlerOptions(),
            []
        );

        $router->registerHandler(FirstEmptyCommand::class, $handler);
        $router->registerHandler(FirstEmptyCommand::class, $handler);
    }

    /**
     * @test
     *
     * @return void
     */
    public function successRegister(): void
    {
        $handler = new DefaultMessageExecutor(
            \Closure::fromCallable(
                function()
                {

                }
            ),
            new HandlerArgumentCollection(),
            new HandlerOptions(),
            []
        );

        $router = new Router();

        static::assertCount(0, $router->match(new FirstEmptyCommand));
        static::assertCount(0, $router->match(new FirstEmptyEvent));

        $router->registerHandler(FirstEmptyCommand::class, $handler);

        $router->registerListener(FirstEmptyEvent::class, $handler);
        $router->registerListener(FirstEmptyEvent::class, $handler);

        static::assertCount(3, $router);
        static::assertCount(1, $router->match(new FirstEmptyCommand));
        static::assertCount(2, $router->match(new FirstEmptyEvent));
    }
}
