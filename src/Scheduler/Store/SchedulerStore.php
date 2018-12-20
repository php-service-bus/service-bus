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
use Desperado\ServiceBus\Scheduler\Data\ScheduledOperation;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;

/**
 *
 */
interface SchedulerStore
{
    /**
     * Extract operation (load and delete)
     *
     * @param ScheduledOperationId $id
     * @param callable(ScheduledOperation|null, ?NextScheduledOperation|null):\Generator $postExtract
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Scheduler\Exceptions\ScheduledOperationNotFound
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function extract(ScheduledOperationId $id, callable $postExtract): Promise;

    /**
     * Remove operation
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param ScheduledOperationId $id
     * @param callable(NextScheduledOperation|null):Generator $postRemove
     *
     * @return Promise<bool>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function remove(ScheduledOperationId $id, callable $postRemove): Promise;

    /**
     * Save new operation
     *
     * @param ScheduledOperation $operation
     * @param callable(ScheduledOperation, NextScheduledOperation|null):Generator $postAdd
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function add(ScheduledOperation $operation, callable $postAdd): Promise;
}
