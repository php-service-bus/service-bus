<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Retry;

use Amp\Promise;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\EntryPoint\Retry\FailureContext;
use ServiceBus\Common\EntryPoint\Retry\RetryStrategy;
use function Amp\call;

final class NullRetryStrategy implements RetryStrategy
{
    public function retry(object $message, ServiceBusContext $context, FailureContext $details): Promise
    {
        return call(
            static function () use ($context): void
            {
                $context->logger()->debug('Message reprocessing not configured');
            }
        );
    }

    public function backoff(object $message, ServiceBusContext $context, FailureContext $details): Promise
    {
        return call(
            static function () use ($context): void
            {
                $context->logger()->debug('Message reprocessing not configured');
            }
        );
    }
}
