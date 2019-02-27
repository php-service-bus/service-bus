<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder\Stubs;

use Amp\Promise;
use Amp\Success;
use ServiceBus\Context\KernelContext;
use ServiceBus\Tests\Stubs\Messages\FirstEmptyCommand;

/**
 *
 */
final class MessageHandlerService
{
    /**
     * @param FirstEmptyCommand $command
     * @param KernelContext     $context
     * @param mixed             $mixedParameter
     *
     * @return Promise
     */
    public function someHandler(
        FirstEmptyCommand $command,
        KernelContext $context,
        $mixedParameter
    ): Promise {
        return new Success([$command, $context, $mixedParameter]);
    }
}
