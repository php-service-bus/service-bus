<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Stubs\Services;

use ServiceBus\Context\KernelContext;
use ServiceBus\Services\Annotations\CommandHandler;
use ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use ServiceBus\Tests\Stubs\Messages\FirstEventWithKey;


/**
 *
 */
final class CorrectService
{
    /**
     * @CommandHandler()
     *
     * @param FirstEmptyCommand $command
     * @param KernelContext     $context
     *
     * @return void
     */
    public function handle(FirstEmptyCommand $command, KernelContext $context): void
    {

    }

    public function listen(FirstEventWithKey $event, KernelContext $context): void
    {

    }
}
