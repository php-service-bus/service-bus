<?php
/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Context;

use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 *
 */
interface ContextFactory
{
    public function create(IncomingPackage $package, object $message): ServiceBusContext;
}
