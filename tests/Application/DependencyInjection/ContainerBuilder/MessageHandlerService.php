<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\Application\DependencyInjection\ContainerBuilder;

use Amp\Promise;
use Amp\Success;
use ServiceBus\Common\Context\ServiceBusContext;

/**
 *
 */
final class MessageHandlerService
{
    public function someHandler(
        EmptyMessage $command,
        ServiceBusContext $context,
        mixed $mixedParameter
    ): Promise {
        return new Success([$command, $context, $mixedParameter]);
    }
}
