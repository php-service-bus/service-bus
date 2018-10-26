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
interface StorageAdapter
{
    /**
     * Execute query
     *
     * @param string $queryString
     * @param array  $parameters
     *
     * @return Promise<\Desperado\ServiceBus\Infrastructure\Storage\ResultSet>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed Basic type of interaction errors
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed Could not connect to database
     */
    public function execute(string $queryString, array $parameters = []): Promise;

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

    /**
     * Unescape binary string
     *
     * @param string|resource $payload
     *
     * @return string
     */
    public function unescapeBinary($payload): string;
}
