<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\MessageHandlers;

use Amp\Promise;
use Amp\Success;
use PHPUnit\Framework\TestCase;
use ServiceBus\MessageHandlers\Handler;
use ServiceBus\MessageHandlers\HandlerArgument;
use ServiceBus\MessageHandlers\HandlerOptions;

/**
 *
 */
final class HandlerTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function voidReturnDeclaration(): void
    {
        $object = new class()
        {
            public function method(): void
            {

            }
        };

        $handler = Handler::commandHandler(
            HandlerOptions::create(),
            new \ReflectionMethod($object, 'method')
        );

        static::assertTrue($handler->hasReturnDeclaration());
        static::assertTrue($handler->returnTypeDeclaration()->isVoid());

        static::assertFalse($handler->returnTypeDeclaration()->isGenerator());
        static::assertFalse($handler->returnTypeDeclaration()->isPromise());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function noneReturnDeclaration(): void
    {
        $object = new class()
        {
            /** @noinspection ReturnTypeCanBeDeclaredInspection */
            public function method()
            {

            }
        };

        $handler = Handler::commandHandler(
            HandlerOptions::create(),
            new \ReflectionMethod($object, 'method')
        );

        static::assertFalse($handler->hasReturnDeclaration());
        static::assertNull($handler->returnTypeDeclaration());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function promiseReturnDeclaration(): void
    {
        $object = new class()
        {
            public function method(): Promise
            {
                return new Success();
            }
        };

        $handler = Handler::commandHandler(
            HandlerOptions::create(),
            new \ReflectionMethod($object, 'method')
        );

        static::assertTrue($handler->hasReturnDeclaration());
        static::assertTrue($handler->returnTypeDeclaration()->isPromise());

        static::assertFalse($handler->returnTypeDeclaration()->isGenerator());
        static::assertFalse($handler->returnTypeDeclaration()->isVoid());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function generatorReturnDeclaration(): void
    {
        $object = new class()
        {
            public function method(): \Generator
            {
                yield from [];
            }
        };

        $handler = Handler::commandHandler(
            HandlerOptions::create(),
            new \ReflectionMethod($object, 'method')
        );

        static::assertTrue($handler->hasReturnDeclaration());
        static::assertTrue($handler->returnTypeDeclaration()->isGenerator());

        static::assertFalse($handler->returnTypeDeclaration()->isPromise());
        static::assertFalse($handler->returnTypeDeclaration()->isVoid());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function scalarReturnDeclaration(): void
    {
        $object = new class()
        {
            public function method(): string
            {
                return '';
            }
        };

        $handler = Handler::commandHandler(
            HandlerOptions::create(),
            new \ReflectionMethod($object, 'method')
        );

        static::assertTrue($handler->hasReturnDeclaration());

        static::assertFalse($handler->returnTypeDeclaration()->isGenerator());
        static::assertFalse($handler->returnTypeDeclaration()->isPromise());
        static::assertFalse($handler->returnTypeDeclaration()->isVoid());
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function scalarArgument(): void
    {
        $object = new class()
        {
            public function method(string $argument): string
            {
                return $argument;
            }
        };

        $handler = Handler::eventListener(
            HandlerOptions::create(),
            new \ReflectionMethod($object, 'method')
        );

        static::assertTrue($handler->hasArguments());
        static::assertCount(1, $handler->arguments());

        $args = \iterator_to_array($handler->arguments());

        /** @var HandlerArgument $argument */
        $argument = \end($args);

        static::assertEquals(\get_class($object), $argument->declaringClass());
        static::assertEquals('argument', $argument->name());
        static::assertTrue($argument->hasType());
        static::assertNull($argument->className());
        static::assertEquals('string', $argument->type());
        static::assertFalse($argument->isObject());
        static::assertFalse($argument->isA(\stdClass::class));

    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function objectArgument(): void
    {
        $object = new class()
        {
            public function method(\stdClass $argument): string
            {
                return (string) $argument->qwerty;
            }
        };

        $handler = Handler::eventListener(
            HandlerOptions::create(),
            new \ReflectionMethod($object, 'method')
        );

        static::assertTrue($handler->hasArguments());
        static::assertCount(1, $handler->arguments());

        $args = \iterator_to_array($handler->arguments());

        /** @var HandlerArgument $argument */
        $argument = \end($args);

        static::assertEquals(\get_class($object), $argument->declaringClass());
        static::assertEquals('argument', $argument->name());
        static::assertTrue($argument->hasType());
        static::assertEquals(\stdClass::class, $argument->className());
        static::assertEquals('object', $argument->type());
        static::assertTrue($argument->isObject());
        static::assertTrue($argument->isA(\stdClass::class));
    }

    /**
     * @test
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function argumentWithoutTypeDeclaration(): void
    {
        $object = new class()
        {
            public function method($argument): \Generator
            {
                yield $argument;
            }
        };

        $handler = Handler::eventListener(
            HandlerOptions::create(),
            new \ReflectionMethod($object, 'method')
        );

        static::assertTrue($handler->hasArguments());
        static::assertCount(1, $handler->arguments());

        $args = \iterator_to_array($handler->arguments());

        /** @var HandlerArgument $argument */
        $argument = \end($args);

        static::assertEquals(\get_class($object), $argument->declaringClass());
        static::assertEquals('argument', $argument->name());
        static::assertFalse($argument->hasType());
        static::assertNull($argument->className());
        static::assertNull($argument->type());
        static::assertFalse($argument->isObject());
        static::assertFalse($argument->isA(\stdClass::class));
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage If an object is not specified, it is assumed that the closure for the operation was
     *                           added earlier (@see Handler::$executionClosure)
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function toClosureWithoutObject(): void
    {
        $object = new class()
        {
            public function method(\stdClass $argument): string
            {
                return (string) $argument->qwerty;
            }
        };

        $handler = Handler::eventListener(
            HandlerOptions::create(),
            new \ReflectionMethod($object, 'method')
        );

        $handler->toClosure();
    }
}
