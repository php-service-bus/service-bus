<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\MessageExecutor;

use PHPUnit\Framework\TestCase;
use ServiceBus\AnnotationsReader\AttributesReader;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\MessageExecutor\DefaultMessageExecutor;
use ServiceBus\MessageExecutor\DefaultMessageExecutorFactory;
use ServiceBus\MessageExecutor\MessageValidationExecutor;
use ServiceBus\Services\Attributes\CommandHandler;
use ServiceBus\Services\Attributes\EventListener;
use ServiceBus\Services\Configuration\AttributeServiceHandlersLoader;
use ServiceBus\Services\Configuration\ServiceMessageHandler;
use ServiceBus\Tests\MessageExecutor\Stubs\TestMessageExecutorMessage;

/**
 *
 */
final class DefaultMessageExecutorFactoryTest extends TestCase
{
    /**
     * @var ServiceMessageHandler[]
     */
    private $handlers;

    protected function setUp(): void
    {
        parent::setUp();

        $service = new class() {

            #[CommandHandler(
                validationEnabled: true,
                validationGroups: ['qwerty']
            )]
            public function handle(
                TestMessageExecutorMessage $command,
                ServiceBusContext $context
            ): void
            {

            }

            #[EventListener]
            public function when(
                TestMessageExecutorMessage $event,
                ServiceBusContext $context
            ): \Generator
            {
                yield from [];
            }
        };

        $this->handlers = \iterator_to_array(
            (new AttributeServiceHandlersLoader(new AttributesReader()))->load($service)
        );
    }

    /** @test */
    public function createHandler(): void
    {
        $messageHandlerFactory = new DefaultMessageExecutorFactory([]);

        /** @var ServiceMessageHandler $commandHandler */
        $commandHandler = $this->handlers[\array_key_first($this->handlers)];

        /** @var ServiceMessageHandler $commandHandler */
        $eventListener = $this->handlers[\array_key_last($this->handlers)];

        self::assertInstanceOf(
            MessageValidationExecutor::class,
            $messageHandlerFactory->create($commandHandler->messageHandler)
        );

        self::assertInstanceOf(
            DefaultMessageExecutor::class,
            $messageHandlerFactory->create($eventListener->messageHandler)
        );
    }
}
