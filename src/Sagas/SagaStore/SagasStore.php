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
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param StoredSaga $savedSaga
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function save(StoredSaga $savedSaga): Promise;

    /**
     * Update the status of an existing saga
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param StoredSaga $savedSaga
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function update(StoredSaga $savedSaga): Promise;

    /**
     * Load saga
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param SagaId $id
     *
     * @return Promise<\Desperado\ServiceBus\Sagas\SagaStore\StoredSaga|null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     * @throws \Desperado\ServiceBus\Sagas\SagaStore\Exceptions\RestoreSagaFailed
     */
    public function load(SagaId $id): Promise;

    /**
     * Remove saga
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param SagaId $id
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function remove(SagaId $id): Promise;
}
