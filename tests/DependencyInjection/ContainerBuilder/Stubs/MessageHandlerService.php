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

namespace Desperado\ServiceBus\Tests\DependencyInjection\ContainerBuilder\Stubs;

use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\SagaProvider;
use Desperado\ServiceBus\Tests\Stubs\Context\TestContext;
use Desperado\ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;

/**
 *
 */
final class MessageHandlerService
{
    /**
     * @param FirstEmptyCommand $command
     * @param TestContext       $context
     * @param SagaProvider      $sagaProvider
     * @param mixed             $mixedParameter
     *
     * @return Promise
     */
    public function someHandler(
        FirstEmptyCommand $command,
        TestContext $context,
        SagaProvider $sagaProvider,
        $mixedParameter
    ): Promise
    {
        return new Success([$command, $context, $sagaProvider, $mixedParameter]);
    }
}
