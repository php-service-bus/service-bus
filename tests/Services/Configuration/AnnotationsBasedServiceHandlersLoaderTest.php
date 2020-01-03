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
use ServiceBus\AnnotationsReader\Exceptions\ParseAnnotationFailed;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\MessageExecutor\MessageHandlerOptions;
use ServiceBus\Services\Configuration\AnnotationsBasedServiceHandlersLoader;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Services\Annotations\EventListener;
use ServiceBus\Services\Configuration\DefaultHandlerOptions;
use ServiceBus\Services\Configuration\ServiceMessageHandler;
use ServiceBus\Services\Exceptions\InvalidEventType;
use ServiceBus\Services\Exceptions\InvalidHandlerArguments;

/**
 *
 */
final class AnnotationsBasedServiceHandlersLoaderTest extends TestCase
{
    /** @test */
    public function loadFromEmptyService(): void
    {
        $object = new class() {
        };

        $handlers = (new AnnotationsBasedServiceHandlersLoader())->load($object);

        static::assertEmpty($handlers);
    }

    /** @test */
    public function loadFilledService(): void
    {
        $service = new class() {
            /**
             * @CommandHandler(
             *     validate=true,
             *     groups={"qwerty", "root"},
             *     description="handle"
             * )
             */
            public function handle(EmptyMessage $command, ServiceBusContext $context): void
            {
            }

            /** @EventListener(description="firstEventListener") */
            public function firstEventListener(EmptyMessage $event, ServiceBusContext $context): Promise
            {
                return new Success([$event, $context]);
            }

            /** @EventListener( description="secondEventListener") */
            public function secondEventListener(EmptyMessage $event, ServiceBusContext $context): \Generator
            {
                yield from [$event, $context];
            }
        };

        $handlers = (new AnnotationsBasedServiceHandlersLoader())->load($service);

        static::assertNotEmpty($handlers);
        static::assertCount(3, $handlers);

        /** @var \ServiceBus\Services\Configuration\ServiceMessageHandler $handler */
        foreach($handlers as $handler)
        {
            /**
             * @var \ServiceBus\Common\MessageHandler\MessageHandler $handler
             * @var DefaultHandlerOptions                            $options
             */
            $options = $handler->messageHandler->options;

            static::assertNotNull($handler->messageHandler->returnDeclaration);

            static::assertSame($handler->messageHandler->methodName, $options->description);

            static::assertTrue($handler->messageHandler->hasArguments);
            static::assertCount(2, $handler->messageHandler->arguments);

            if(true === $handler->messageHandler->options->isCommandHandler)
            {
                static::assertSame(EmptyMessage::class, $handler->messageHandler->messageClass);
                static::assertInstanceOf(\Closure::class, $handler->messageHandler->closure);

                static::assertTrue($options->validationEnabled);
                static::assertSame(['qwerty', 'root'], $options->validationGroups);

                static::assertSame('handle', $handler->messageHandler->methodName);
            }
            else
            {
                static::assertSame(EmptyMessage::class, $handler->messageHandler->messageClass);
                static::assertInstanceOf(\Closure::class, $handler->messageHandler->closure);

                static::assertFalse($options->validationEnabled);
                static::assertEmpty($options->validationGroups);
            }
        }
    }

    /** @test */
    public function loadHandlerWithNoArguments(): void
    {
        $this->expectException(InvalidHandlerArguments::class);
        $this->expectExceptionMessage('The event handler must have at least 2 arguments: the message object (the first argument) and the context');

        $service = new class() {
            /**
             * @CommandHandler(
             *     validate=true,
             *     groups={"qwerty", "root"}
             * )
             */
            public function handle(): void
            {
            }
        };

        (new AnnotationsBasedServiceHandlersLoader())->load($service);
    }

    /** @test */
    public function loadHandlerWithWrongMessageArgument(): void
    {
        $this->expectException(InvalidHandlerArguments::class);
        $this->expectExceptionMessage('The first argument to the message handler must be the message object');

        $service = new class() {
            /**
             * @CommandHandler(
             *     validate=true,
             *     groups={"qwerty", "root"}
             * )
             */
            public function handle(string $qwerty, ServiceBusContext $context): void
            {
            }
        };

        (new AnnotationsBasedServiceHandlersLoader())->load($service);
    }

    /** @test */
    public function withUnsupportedAnnotation(): void
    {
        $service = new class() {
            /** @\ServiceBus\Tests\Services\Configuration\UnsupportedAnnotation() */
            public function handle(): void
            {
            }

            /** @EventListener() */
            public function firstEventListener(EmptyMessage $event, ServiceBusContext $context): Promise
            {
                return new Success([$event, $context]);
            }
        };

        $handlers = \iterator_to_array((new AnnotationsBasedServiceHandlersLoader())->load($service));

        static::assertCount(1, $handlers);

        /** @var \ServiceBus\Services\Configuration\ServiceMessageHandler $handler */
        $handler = \end($handlers);

        static::assertInstanceOf(ServiceMessageHandler::class, $handler);
        static::assertSame('firstEventListener', $handler->messageHandler->methodName);
    }

    /** @test */
    public function withUnknownAnnotation(): void
    {
        $this->expectException(ParseAnnotationFailed::class);

        $service = new class() {
            /** @sefsefsef */
            public function handle(): void
            {
            }
        };

        (new AnnotationsBasedServiceHandlersLoader())->load($service);
    }

    /** @test */
    public function withIncorrectDefaultValidationFailedEvent(): void
    {
        $this->expectException(InvalidEventType::class);

        $service = new class() {
            /**
             * @CommandHandler(
             *     validate=true,
             *     groups={"qwerty", "root"},
             *     description="handle",
             *     defaultValidationFailedEvent="\ServiceBus\Tests\Services\Configuration\IncorrectValidationFailedEvent"
             * )
             */
            public function handle(EmptyMessage $command, ServiceBusContext $context): void
            {
            }

        };

        (new AnnotationsBasedServiceHandlersLoader())->load($service);
    }

    /** @test */
    public function withIncorrectDefaultThrowableEvent(): void
    {
        $this->expectException(InvalidEventType::class);

        $service = new class() {
            /**
             * @CommandHandler(
             *     validate=true,
             *     groups={"qwerty", "root"},
             *     description="handle",
             *     defaultThrowableEvent="\ServiceBus\Tests\Services\Configuration\IncorrectDefaultThrowableEvent"
             * )
             */
            public function handle(EmptyMessage $command, ServiceBusContext $context): void
            {
            }

        };

        (new AnnotationsBasedServiceHandlersLoader())->load($service);
    }

    /** @test */
    public function withDefaultValidationFailedEvent(): void
    {
        $service = new class() {
            /**
             * @CommandHandler(
             *     validate=true,
             *     groups={"qwerty", "root"},
             *     description="handle",
             *     defaultValidationFailedEvent="\ServiceBus\Tests\Services\Configuration\CorrectValidationFailedEvent"
             * )
             */
            public function handle(EmptyMessage $command, ServiceBusContext $context): void
            {
            }

        };

        $handlers = \iterator_to_array((new AnnotationsBasedServiceHandlersLoader())->load($service));

        static::assertCount(1, $handlers);

        /** @var \ServiceBus\Services\Configuration\ServiceMessageHandler $handler */
        $handler = \end($handlers);

        /** @var DefaultHandlerOptions $options */
        $options = $handler->messageHandler->options;

        static::assertSame(
            '\\' . CorrectValidationFailedEvent::class, $options->defaultValidationFailedEvent
        );
    }

    /** @test */
    public function withDefaultThrowableEvent(): void
    {
        $service = new class() {
            /**
             * @CommandHandler(
             *     validate=true,
             *     groups={"qwerty", "root"},
             *     description="handle",
             *     defaultThrowableEvent="\ServiceBus\Tests\Services\Configuration\CorrectDefaultThrowableEvent"
             * )
             */
            public function handle(EmptyMessage $command, ServiceBusContext $context): void
            {
            }

        };

        $handlers = \iterator_to_array((new AnnotationsBasedServiceHandlersLoader())->load($service));

        static::assertCount(1, $handlers);

        /** @var \ServiceBus\Services\Configuration\ServiceMessageHandler $handler */
        $handler = \end($handlers);

        /** @var DefaultHandlerOptions $options */
        $options = $handler->messageHandler->options;

        static::assertSame(
            '\\' . CorrectDefaultThrowableEvent::class, $options->defaultThrowableEvent
        );
    }
}
