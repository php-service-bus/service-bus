<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\Bootstrap\services;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Services\Annotations\EventListener;

/**
 *
 */
final class TestBootstrapService
{
    /**
     * @CommandHandler()
     */
    public function do(TestBootstrapCommand $command, ServiceBusContext $context): void
    {
    }

    /**
     * @EventListener()
     */
    public function when(TestBootstrapEven $event, ServiceBusContext $context): void
    {
    }
}
