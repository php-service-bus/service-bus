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
 * Interface adapter for working with the database
 */
interface StorageAdapter extends QueryExecutor, BinaryDataDecoder
{
    /**
     * Does the transaction adapter
     *
     * @return bool
     */
    public function supportsTransaction(): bool;

    /**
     * Start transaction
     *
     * @return Promise<\Desperado\ServiceBus\Infrastructure\Storage\TransactionAdapter>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\TransactionNotSupported The adapter does not support transactions
     */
    public function transaction(): Promise;
}
