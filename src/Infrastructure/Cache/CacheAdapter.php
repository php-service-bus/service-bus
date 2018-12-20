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

namespace Desperado\ServiceBus\Infrastructure\Cache;

use Amp\Promise;

/**
 * Cache adapter
 */
interface CacheAdapter
{
    /**
     * Receive stored entry
     *
     * @param string $key
     *
     * @return Promise<int|string|float|null|array>
     */
    public function get(string $key): Promise;

    /**
     * Has stored entry
     *
     * @param string $key
     *
     * @return Promise<bool>
     */
    public function has(string $key): Promise;

    /**
     * Remove entry
     *
     * @param string $key
     *
     * @return Promise<bool>
     */
    public function remove(string $key): Promise;

    /**
     * Save new cache entry
     *
     * @param string                      $key
     * @param int|string|float|null|array $value
     * @param int                         $ttl
     *
     * @return Promise<bool>
     */
    public function save(string $key, $value, int $ttl = 0): Promise;

    /**
     * Clear storage
     *
     * @return Promise<bool>
     */
    public function clear(): Promise;
}
