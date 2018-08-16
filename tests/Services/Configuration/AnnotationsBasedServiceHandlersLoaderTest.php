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

use Desperado\ServiceBus\Services\Configuration\AnnotationsBasedServiceHandlersLoader;
use Desperado\ServiceBus\Tests\Services\Configuration\Stubs\ServiceWithCorrectMessageHandlers;
use Desperado\ServiceBus\Tests\Services\Configuration\Stubs\ServiceWithoutMessageHandlers;
use Desperado\ServiceBus\Tests\Services\Configuration\Stubs\SomeCommand;
use Desperado\ServiceBus\Tests\Services\Configuration\Stubs\SomeEvent;
use PHPUnit\Framework\TestCase;

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
        $handlers = (new AnnotationsBasedServiceHandlersLoader())->load(new ServiceWithoutMessageHandlers());

        static::assertEmpty($handlers);
    }

    /**
     * @test
     *
     * @return void
     */
    public function loadFilledService(): void
    {
        $service = new ServiceWithCorrectMessageHandlers();

        $handlers = (new AnnotationsBasedServiceHandlersLoader())->load($service);

        static::assertNotEmpty($handlers);
        static::assertCount(3, $handlers);

        foreach($handlers as $handler)
        {
            /** @var \Desperado\ServiceBus\MessageBus\MessageHandler\Handler $handler */

            $options = $handler->options();

            static::assertTrue($handler->hasReturnDeclaration());

            static::assertTrue($handler->hasArguments());
            static::assertCount(2, $handler->arguments());

            if(true === $handler->isCommandHandler())
            {
                static::assertEquals(SomeCommand::class, $handler->messageClass());
                /** @noinspection UnnecessaryAssertionInspection */
                static::assertInstanceOf(\Closure::class, $handler->toClosure($service));

                static::assertTrue($options->validationEnabled());
                static::assertEquals(['qwerty', 'root'],$options->validationGroups());

                static::assertEquals('handle', $handler->methodName());
            }
            else
            {
                static::assertEquals(SomeEvent::class, $handler->messageClass());
                /** @noinspection UnnecessaryAssertionInspection */
                static::assertInstanceOf(\Closure::class, $handler->toClosure($service));

                static::assertFalse($options->validationEnabled());
                static::assertEmpty($options->validationGroups());
            }
        }
    }
}
