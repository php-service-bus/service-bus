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

namespace Desperado\ServiceBus\Scheduler\Store;

use Amp\Promise;

/**
 *
 */
interface SchedulerStore
{
    /**
     * Load registry
     *
     * @param string $id
     *
     * @return Promise<\Desperado\ServiceBus\Scheduler\Store\SchedulerRegistry>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function load(string $id): Promise;

    /**
     * Save new registry
     *
     * @param SchedulerRegistry $registry
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationCheckFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function add(SchedulerRegistry $registry): Promise;

    /**
     * Update registry
     *
     * @param SchedulerRegistry $registry
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function update(SchedulerRegistry $registry): Promise;
}
