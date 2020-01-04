<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\MessageExecutor;

use PHPUnit\Framework\TestCase;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\MessageExecutor\DefaultMessageExecutor;
use ServiceBus\MessageExecutor\DefaultMessageExecutorFactory;
use ServiceBus\MessageExecutor\MessageValidationExecutor;
use ServiceBus\Services\Configuration\AnnotationsBasedServiceHandlersLoader;
use ServiceBus\Services\Configuration\ServiceMessageHandler;
use ServiceBus\Tests\Services\Configuration\EmptyMessage;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Services\Annotations\EventListener;

/**
 *
 */
final class DefaultMessageExecutorFactoryTest extends TestCase
{
    /**
     * @var ServiceMessageHandler[]
     */
    private $handlers;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $service = new class()
        {
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

            /** @EventListener( description="secondEventListener") */
            public function secondEventListener(EmptyMessage $event, ServiceBusContext $context): \Generator
            {
                yield from [$event, $context];
            }
        };

        $this->handlers = \iterator_to_array((new AnnotationsBasedServiceHandlersLoader())->load($service));
    }

    /** @test */
    public function createHandler(): void
    {
        $messageHandlerFactory = new DefaultMessageExecutorFactory([]);

        /** @var ServiceMessageHandler $commandHandler */
        $commandHandler = $this->handlers[\array_key_first($this->handlers)];

        /** @var ServiceMessageHandler $commandHandler */
        $eventListener = $this->handlers[\array_key_last($this->handlers)];

        static::assertInstanceOf(
            MessageValidationExecutor::class,
            $messageHandlerFactory->create($commandHandler->messageHandler)
        );

        static::assertInstanceOf(
            DefaultMessageExecutor::class,
            $messageHandlerFactory->create($eventListener->messageHandler)
        );
    }
}
