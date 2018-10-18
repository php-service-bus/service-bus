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

namespace Desperado\ServiceBus\Services\Configuration;

use Desperado\ServiceBus\MessageHandlers\HandlerCollection;

/**
 * Retrieving a list of message handlers for the specified object
 */
interface ServiceHandlersLoader
{
    /**
     * Load specified saga listeners
     *
     * @param object $service
     *
     * @return HandlerCollection
     */
    public function load(object $service): HandlerCollection;
}
