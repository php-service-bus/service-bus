<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Services\Configuration;

/**
 * Retrieving a list of message handlers for the specified object.
 */
interface ServiceHandlersLoader
{
    /**
     * Load specified saga listeners.
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidHandlerArguments
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType
     * @throws \ServiceBus\Services\Exceptions\UnableCreateClosure
     */
    public function load(object $service): \SplObjectStorage;
}
