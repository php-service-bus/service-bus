<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Context;

use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Common\Context\ServiceBusContext;

/**
 *
 */
interface ContextFactory
{
    /**
     * @psalm-param  array<string, int|float|string|null> $headers
     */
    public function create(object $message, array $headers, IncomingMessageMetadata $metadata): ServiceBusContext;
}
