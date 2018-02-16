<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Storage;

use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;

/**
 * Saga storage backend interface
 */
interface SagaStorageInterface
{
    /**
     * Save saga
     *
     * @param StoredSaga $storedSaga
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageConnectionException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageException
     */
    public function save(StoredSaga $storedSaga): void;

    /**
     * Update exists saga
     *
     * @param StoredSaga $storedSaga
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageConnectionException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageException
     */
    public function update(StoredSaga $storedSaga): void;

    /**
     * Load saga
     *
     * @param AbstractSagaIdentifier $id
     *
     * @return StoredSaga|null
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageConnectionException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageException
     */
    public function load(AbstractSagaIdentifier $id): ?StoredSaga;

    /**
     * Delete saga
     *
     * @param AbstractSagaIdentifier $id
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageConnectionException
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageException
     */
    public function remove(AbstractSagaIdentifier $id): void;
}
