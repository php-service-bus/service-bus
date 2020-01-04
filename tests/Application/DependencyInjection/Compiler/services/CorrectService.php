<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\DependencyInjection\Compiler\services;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Services\Annotations\CommandHandler;

/**
 *
 */
final class CorrectService
{
    /**
     * @CommandHandler()
     */
    public function handle(EmptyMessage $command, ServiceBusContext $context): void
    {
    }

    public function listen(EmptyMessage $event, ServiceBusContext $context): void
    {
    }
}
