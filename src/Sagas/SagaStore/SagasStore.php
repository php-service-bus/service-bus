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
     * @return \Generator It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function save(StoredSaga $savedSaga): \Generator;

    /**
     * Update the status of an existing saga
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param StoredSaga $savedSaga
     *
     * @return \Generator It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function update(StoredSaga $savedSaga): \Generator;

    /**
     * Load saga
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param SagaId $id
     *
     * @return \Generator<\Desperado\ServiceBus\Sagas\SagaStore\StoredSaga|null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     * @throws \Desperado\ServiceBus\Sagas\SagaStore\Exceptions\RestoreSagaFailed
     */
    public function load(SagaId $id): \Generator;

    /**
     * Remove saga
     *
     * @psalm-suppress InvalidReturnType Incorrect resolving the value of the generator
     *
     * @param SagaId $id
     *
     * @return \Generator It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function remove(SagaId $id): \Generator;
}
