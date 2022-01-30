<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\Services\Configuration;

use Amp\Promise;
use Amp\Success;
use PHPUnit\Framework\TestCase;
use ServiceBus\AnnotationsReader\AttributesReader;
use ServiceBus\AnnotationsReader\Exceptions\ParseAttributesFailed;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Services\Attributes\CommandHandler;
use ServiceBus\Services\Attributes\EventListener;
use ServiceBus\Services\Configuration\AttributeServiceHandlersLoader;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use ServiceBus\Services\Configuration\ServiceMessageHandler;
use ServiceBus\Services\Exceptions\InvalidHandlerArguments;
use ServiceBus\Tests\Services\Configuration\Stubs\TestConfigurationLoaderMessage;
use ServiceBus\Tests\Services\Configuration\Stubs\UnsupportedAttribute;

/**
 *
 */
final class AttributeServiceHandlersLoaderTest extends TestCase
{
    /**
     * @var AttributeServiceHandlersLoader
     */
    private $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = new AttributeServiceHandlersLoader(
            new AttributesReader()
        );
    }

    /**
     * @test
     */
    public function loadFromEmptyService(): void
    {
        $object = new class ()
        {
        };

        $handlers = $this->loader->load($object);

        self::assertEmpty($handlers);
    }

    /**
     * @test
     */
    public function loadFilledService(): void
    {
        $service = new class ()
        {
            #[CommandHandler(
                description: 'handle',
                validationEnabled: true,
                validationGroups: ['qwerty', 'root'],
                executionTimeout: 120
            )]
            public function handle(
                TestConfigurationLoaderMessage $command,
                ServiceBusContext $context
            ): void {
            }

            #[EventListener(
                description: 'firstEventListener'
            )]
            public function firstEventListener(
                TestConfigurationLoaderMessage $event,
                ServiceBusContext $context
            ): Promise {
                return new Success([$event, $context]);
            }

            #[EventListener(
                description: 'secondEventListener'
            )]
            public function secondEventListener(
                TestConfigurationLoaderMessage $event,
                ServiceBusContext $context
            ): \Generator {
                yield from [$event, $context];
            }
        };

        $handlers = $this->loader->load($service);

        self::assertNotEmpty($handlers);
        self::assertCount(3, $handlers);

        /** @var \ServiceBus\Services\Configuration\ServiceMessageHandler $handler */
        foreach ($handlers as $handler)
        {
            /**
             * @var \ServiceBus\Common\MessageHandler\MessageHandler $handler
             * @var DefaultHandlerOptions                            $options
             */
            $options = $handler->messageHandler->options;

            self::assertNotNull($handler->messageHandler->returnDeclaration);

            self::assertSame($handler->messageHandler->methodName, $options->description);

            self::assertTrue($handler->messageHandler->hasArguments);
            self::assertCount(2, $handler->messageHandler->arguments);

            /** @noinspection PhpPossiblePolymorphicInvocationInspection */
            if ($handler->messageHandler->options->isCommandHandler)
            {
                self::assertSame(TestConfigurationLoaderMessage::class, $handler->messageHandler->messageClass);
                self::assertInstanceOf(\Closure::class, $handler->messageHandler->closure);

                self::assertTrue($options->validationEnabled);
                self::assertSame(['qwerty', 'root'], $options->validationGroups);

                self::assertSame('handle', $handler->messageHandler->methodName);
            }
            else
            {
                self::assertSame(TestConfigurationLoaderMessage::class, $handler->messageHandler->messageClass);
                self::assertInstanceOf(\Closure::class, $handler->messageHandler->closure);

                self::assertFalse($options->validationEnabled);
                self::assertEmpty($options->validationGroups);
            }
        }
    }

    /**
     * @test
     */
    public function loadHandlerWithNoArguments(): void
    {
        $this->expectException(InvalidHandlerArguments::class);
        $this->expectExceptionMessage(
            'The event handler must have at least 2 arguments: the message object (the first argument) and the context'
        );

        $service = new class ()
        {
            #[CommandHandler]
            public function handle(): void
            {
            }
        };

        $this->loader->load($service);
    }

    /**
     * @test
     */
    public function loadHandlerWithWrongMessageArgument(): void
    {
        $this->expectException(InvalidHandlerArguments::class);
        $this->expectExceptionMessage('The first argument to the message handler must be the message object');

        $service = new class ()
        {
            #[CommandHandler]
            public function handle(
                string $qwerty,
                ServiceBusContext $context
            ): void {
            }
        };

        $this->loader->load($service);
    }

    /**
     * @test
     */
    public function withUnsupportedAttribute(): void
    {
        $service = new class ()
        {
            #[UnsupportedAttribute]
            public function handle(): void
            {
            }

            #[EventListener]
            public function firstEventListener(
                TestConfigurationLoaderMessage $event,
                ServiceBusContext $context
            ): Promise {
                return new Success([$event, $context]);
            }
        };

        $handlers = \iterator_to_array($this->loader->load($service));

        self::assertCount(1, $handlers);

        /** @var \ServiceBus\Services\Configuration\ServiceMessageHandler $handler */
        $handler = \end($handlers);

        self::assertInstanceOf(ServiceMessageHandler::class, $handler);
        self::assertSame('firstEventListener', $handler->messageHandler->methodName);
    }

    /**
     * @test
     */
    public function withUnknownAttribute(): void
    {
        $this->expectException(ParseAttributesFailed::class);

        $service = new class ()
        {
            /** @noinspection PhpUndefinedClassInspection */
            #[AAsdfsf]
            public function handle(): void
            {
            }
        };

        $this->loader->load($service);
    }
}
