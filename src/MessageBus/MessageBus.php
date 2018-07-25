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

namespace Desperado\ServiceBus\MessageBus;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Application\KernelContext;

/**
 *
 */
final class MessageBus
{

    /**
     * @param KernelContext $context
     *
     * @return Promise
     */
    public function dispatch(KernelContext $context): Promise
    {
        /** @psalm-suppress InvalidArgument */
        return call(
            static function(KernelContext $context): void
            {

            },
            $context
        );
    }
}
