<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

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
        $mixedParameter
    ): Promise {
        return new Success([$command, $context, $mixedParameter]);
    }
}
