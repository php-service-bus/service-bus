<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Configuration;

/**
 * Retrieving a list of message handlers for the specified object.
 */
interface ServiceHandlersLoader
{
    /**
     * Load specified saga listeners.
     *
     * @psalm-return \SplObjectStorage<\ServiceBus\Services\Configuration\ServiceMessageHandler, string>
     *
     * @param object $service
     *
     * @throws \ServiceBus\Services\Exceptions\InvalidHandlerArguments
     * @throws \ServiceBus\Services\Exceptions\InvalidEventType
     * @throws \ServiceBus\Services\Exceptions\UnableCreateClosure
     * @throws \ServiceBus\AnnotationsReader\Exceptions\ParseAnnotationFailed
     *
     * @return \SplObjectStorage
     */
    public function load(object $service): \SplObjectStorage;
}
