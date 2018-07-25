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
     * @return Promise<\Desperado\ServiceBus\Storage\ResultSet>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
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
     * @return Promise<\Desperado\ServiceBus\Storage\TransactionAdapter>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\TransactionNotSupported
     */
    public function transaction(): Promise;

    /**
     * Unescape binary string
     *
     * @param string $string
     *
     * @return string
     */
    public function unescapeBinary(string $string): string;
}
