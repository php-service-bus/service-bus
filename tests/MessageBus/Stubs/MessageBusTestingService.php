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

namespace Desperado\ServiceBus\Tests\MessageBus\Stubs;

use Desperado\ServiceBus\Services\Annotations\CommandHandler;
use Desperado\ServiceBus\Services\Annotations\EventListener;

/**
 *
 */
final class MessageBusTestingService
{
    /**
     * @CommandHandler()
     *
     * @param MessageBusTestingCommand $command
     * @param MessageBusTestingContext $context
     *
     * @return void
     */
    public function commandHandler(
        MessageBusTestingCommand $command,
        MessageBusTestingContext $context
    ): void
    {

    }

    /**
     * @EventListener(
     *     validate=true,
     *     groups={"testing"}
     * )
     *
     * @param MessageBusTestingEvent   $event
     * @param MessageBusTestingContext $context
     *
     * @return \Generator
     */
    public function eventListener(
        MessageBusTestingEvent $event,
        MessageBusTestingContext $context
    ): \Generator
    {

    }
}
