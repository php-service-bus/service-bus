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

namespace Desperado\ServiceBus\Index\Storage;

use Amp\Promise;

/**
 *
 */
interface IndexesStorage
{
    /**
     * @param string $indexKey
     * @param string $valueKey
     *
     * @return Promise<string|int|float|boolean|null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function find(string $indexKey, string $valueKey): Promise;

    /**
     * @param string $indexKey
     * @param string $valueKey
     * @param mixed  $value
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function add(string $indexKey, string $valueKey, $value): Promise;

    /**
     * @param string $indexKey
     * @param string $valueKey
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function delete(string $indexKey, string $valueKey): Promise;

    /**
     * @param string $indexKey
     * @param string $valueKey
     * @param mixed  $value
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function update(string $indexKey, string $valueKey, $value): Promise;
}
