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

namespace Desperado\ServiceBus\Infrastructure\Storage;

use Amp\Promise;

/**
 * Storage adapters Interface
 */
interface TransactionAdapter extends QueryExecutor, BinaryDataDecoder
{
    /**
     * Commit transaction
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed Duplicate entry
     */
    public function commit(): Promise;

    /**
     * Rollback transaction
     *
     * @return Promise It does not return any result
     */
    public function rollback(): Promise;
}
