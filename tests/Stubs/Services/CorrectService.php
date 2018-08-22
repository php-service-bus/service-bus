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

namespace Desperado\ServiceBus\Tests\Stubs\Services;

use Desperado\ServiceBus\Services\Annotations\CommandHandler;
use Desperado\ServiceBus\Tests\Stubs\Context\TestContext;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEventWithKey;

/**
 *
 */
final class CorrectService
{
    /**
     * @CommandHandler()
     *
     * @param FirstEmptyCommand $command
     * @param TestContext       $context
     *
     * @return void
     */
    public function handle(FirstEmptyCommand $command, TestContext $context): void
    {

    }

    public function listen(FirstEventWithKey $event, TestContext $context): void
    {

    }
}
