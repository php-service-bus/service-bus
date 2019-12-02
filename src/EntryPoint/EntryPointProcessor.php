<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\EntryPoint;

use Amp\Promise;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 * Handling incoming package processor.
 * Responsible for deserialization, routing and task execution.
 */
interface EntryPointProcessor
{
    /**
     * Handle package.
     */
    public function handle(IncomingPackage $package): Promise;
}
