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

namespace Desperado\ServiceBus\Tests\Services\Configuration;

use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Services\Configuration\AnnotationsBasedServiceHandlersLoader;
use Desperado\ServiceBus\Tests\Stubs\Context\TestContext;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent;
use PHPUnit\Framework\TestCase;
use Desperado\ServiceBus\Services\Annotations\EventListener;
use Desperado\ServiceBus\Services\Annotations\CommandHandler;

/**
 *
 */
final class AnnotationsBasedServiceHandlersLoaderTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function loadFromEmptyService(): void
    {
        $object = new class()
        {

        };

        $handlers = (new AnnotationsBasedServiceHandlersLoader())->load($object);

        static::assertEmpty($handlers);
    }

    /**
     * @test
     *
     * @return void
     */
    public function loadFilledService(): void
    {
        $service = new class()
        {
            /**
             * @CommandHandler(
             *     validate=true,
             *     groups={"qwerty", "root"}
             * )
             *
             * @param FirstEmptyCommand $command
             *
             * @return void
             */
            public function handle(FirstEmptyCommand $command, TestContext $context): void
            {

            }

            /**
             * @EventListener()
             *
             * @param FirstEmptyEvent $event
             * @param TestContext     $context
             *
             * @return Promise
             */
            public function firstEventListener(FirstEmptyEvent $event, TestContext $context): Promise
            {
                return new Success([$event, $context]);
            }

            /**
             * @EventListener()
             *
             * @param FirstEmptyEvent $event
             * @param TestContext     $context
             *
             * @return \Generator
             */
            public function secondEventListener(FirstEmptyEvent $event, TestContext $context): \Generator
            {
                yield from [$event, $context];
            }

            /**
             * @Desperado\ServiceBus\Tests\Services\Configuration\SomeAnotherMethodLevelAnnotation
             *
             * @param FirstEmptyCommand $command
             * @param TestContext       $context
             *
             * @return void
             */
            public function ignoredMethod(FirstEmptyCommand $command, TestContext $context): void
            {

            }
        };

        $handlers = (new AnnotationsBasedServiceHandlersLoader())->load($service);

        static::assertNotEmpty($handlers);
        static::assertCount(3, $handlers);

        foreach($handlers as $handler)
        {
            /** @var \Desperado\ServiceBus\MessageHandlers\Handler $handler */

            $options = $handler->options();

            static::assertTrue($handler->hasReturnDeclaration());

            static::assertTrue($handler->hasArguments());
            static::assertCount(2, $handler->arguments());

            if(true === $handler->isCommandHandler())
            {
                static::assertEquals(FirstEmptyCommand::class, $handler->messageClass());
                /** @noinspection UnnecessaryAssertionInspection */
                static::assertInstanceOf(\Closure::class, $handler->toClosure($service));

                static::assertTrue($options->validationEnabled());
                static::assertEquals(['qwerty', 'root'], $options->validationGroups());

                static::assertEquals('handle', $handler->methodName());
            }
            else
            {
                static::assertEquals(FirstEmptyEvent::class, $handler->messageClass());
                /** @noinspection UnnecessaryAssertionInspection */
                static::assertInstanceOf(\Closure::class, $handler->toClosure($service));

                static::assertFalse($options->validationEnabled());
                static::assertEmpty($options->validationGroups());
            }
        }
    }
}
