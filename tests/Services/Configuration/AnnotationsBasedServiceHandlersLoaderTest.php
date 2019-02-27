<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Services\Configuration;

use Amp\Promise;
use Amp\Success;
use PHPUnit\Framework\TestCase;
use ServiceBus\Context\KernelContext;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Services\Annotations\EventListener;
use ServiceBus\Services\Configuration\AnnotationsBasedServiceHandlersLoader;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use ServiceBus\Tests\Stubs\Messages\FirstEmptyEvent;

/**
 *
 */
final class AnnotationsBasedServiceHandlersLoaderTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Throwable
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
     * @throws \Throwable
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
            public function handle(FirstEmptyCommand $command, KernelContext $context): void
            {
            }

            /**
             * @EventListener()
             *
             * @param FirstEmptyEvent $event
             * @param KernelContext   $context
             *
             * @return Promise
             */
            public function firstEventListener(FirstEmptyEvent $event, KernelContext $context): Promise
            {
                return new Success([$event, $context]);
            }

            /**
             * @EventListener()
             *
             * @param FirstEmptyEvent $event
             * @param KernelContext   $context
             *
             * @return \Generator
             */
            public function secondEventListener(FirstEmptyEvent $event, KernelContext $context): \Generator
            {
                yield from [$event, $context];
            }

            /**
             * @ServiceBus\Tests\Services\Configuration\SomeAnotherMethodLevelAnnotation
             *
             * @param FirstEmptyCommand $command
             * @param KernelContext     $context
             *
             * @return void
             */
            public function ignoredMethod(FirstEmptyCommand $command, KernelContext $context): void
            {
            }
        };

        $handlers = (new AnnotationsBasedServiceHandlersLoader())->load($service);

        static::assertNotEmpty($handlers);
        static::assertCount(3, $handlers);

        /** @var \ServiceBus\Services\Configuration\ServiceMessageHandler $handler */
        foreach ($handlers as $handler)
        {
            /**
             * @var \ServiceBus\Common\MessageHandler\MessageHandler $handler
             * @var DefaultHandlerOptions                            $options
             */
            $options = $handler->messageHandler->options;

            static::assertNotNull($handler->messageHandler->returnDeclaration);

            static::assertTrue($handler->messageHandler->hasArguments);
            static::assertCount(2, $handler->messageHandler->arguments);

            if (true === $handler->messageHandler->options->isCommandHandler)
            {
                static::assertSame(FirstEmptyCommand::class, $handler->messageHandler->messageClass);
                /** @noinspection UnnecessaryAssertionInspection */
                static::assertInstanceOf(\Closure::class, $handler->messageHandler->closure);

                static::assertTrue($options->validationEnabled);
                static::assertSame(['qwerty', 'root'], $options->validationGroups);

                static::assertSame('handle', $handler->messageHandler->methodName);
            }
            else
            {
                static::assertSame(FirstEmptyEvent::class, $handler->messageHandler->messageClass);
                /** @noinspection UnnecessaryAssertionInspection */
                static::assertInstanceOf(\Closure::class, $handler->messageHandler->closure);

                static::assertFalse($options->validationEnabled);
                static::assertEmpty($options->validationGroups);
            }
        }
    }
}
