<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler\Storage;

/**
 * The storage interface for scheduled tasks
 */
interface SchedulerStorageInterface
{
    /**
     * Load registry data
     *
     * @param string $id
     *
     * @return string|null
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageConnectionException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageException
     */
    public function load(string $id): ?string;

    /**
     * Store new registry
     *
     * @param string $id
     * @param string $registryPayload
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageConnectionException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageException
     */
    public function add(string $id, string $registryPayload): void;

    /**
     * Update exists registry
     *
     * @param string $id
     * @param string $registryPayload
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageConnectionException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageException
     */
    public function update(string $id, string $registryPayload): void;
}
