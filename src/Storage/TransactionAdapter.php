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

namespace Desperado\ServiceBus\Storage;

use Amp\Promise;

/**
 * Storage adapters Interface
 */
interface TransactionAdapter
{
    /**
     * Execute query
     *
     * @param string $queryString
     * @param array  $parameters Key\value query parameters (prepared statement)
     *
     * @return Promise<\Desperado\ServiceBus\Storage\ResultSet>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed Could not connect to database
     * @throws \Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationCheckFailed Duplicate entry
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed Operation Errors
     */
    public function execute(string $queryString, array $parameters = []): Promise;

    /**
     * Commit transaction
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed Could not connect to database
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed Operation Errors
     * @throws \Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationCheckFailed Duplicate entry
     */
    public function commit(): Promise;

    /**
     * Rollback transaction
     *
     * @return Promise<null>
     */
    public function rollback(): Promise;
}
