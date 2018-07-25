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

namespace Desperado\ServiceBus\Sagas\SagaStore;

use Amp\Promise;
use Desperado\ServiceBus\Sagas\SagaId;

/**
 * Sagas storage
 */
interface SagasStore
{
    /**
     * Save the new saga
     *
     * @param StoredSaga $savedSaga
     * @param callable   $afterSaveHandler The handler is called after the data is saved. If transactions are supported,
     *                                     it will be called before the commit
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationCheckFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function save(StoredSaga $savedSaga, callable $afterSaveHandler): Promise;

    /**
     * Update the status of an existing saga
     *
     * @param StoredSaga $savedSaga
     * @param callable   $afterSaveHandler The handler is called after the data is saved. If transactions are supported,
     *                                     it will be called before the commit
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function update(StoredSaga $savedSaga, callable $afterSaveHandler): Promise;

    /**
     * Load saga
     *
     * @param SagaId $id
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<\Desperado\ServiceBus\Sagas\SagaStore\StoredSaga|null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     * @throws \Desperado\ServiceBus\Sagas\SagaStore\Exceptions\RestoreSagaFailed
     */
    public function load(SagaId $id): Promise;

    /**
     * Remove saga
     *
     * @param SagaId $id
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function remove(SagaId $id): Promise;
}