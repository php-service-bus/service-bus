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

/**
 *
 */
final class MessageBusTestingServiceWithoutMessageArgument
{
    /**
     * @CommandHandler()
     *
     * @param \stdClass                $command
     * @param MessageBusTestingContext $context
     *
     * @return void
     */
    public function commandHandler(
        \stdClass $command,
        MessageBusTestingContext $context
    ): void
    {

    }
}
