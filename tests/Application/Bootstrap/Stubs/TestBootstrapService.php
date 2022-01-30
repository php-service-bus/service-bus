<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\Application\Bootstrap\Stubs;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Services\Attributes\CommandHandler;
use ServiceBus\Services\Attributes\EventListener;

/**
 *
 */
final class TestBootstrapService
{
    #[CommandHandler]
    public function do(TestBootstrapCommand $command, ServiceBusContext $context): void
    {
    }

    #[EventListener]
    public function when(TestBootstrapEvent $event, ServiceBusContext $context): void
    {
    }
}
