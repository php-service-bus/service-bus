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

namespace Desperado\ServiceBus\EntryPoint;

use Amp\Promise;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;

/**
 * Handling incoming package
 */
interface EntryPointProcessor
{
    /**
     * Handle package
     *
     * @param IncomingPackage $package
     *
     * @return Promise
     */
    public function handle(IncomingPackage $package): Promise;
}
