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
use Amp\Success;

/**
 *
 */
final class InMemoryCacheAdapter implements CacheAdapter
{
    /**
     * @var InMemoryStorage
     */
    private $storage;

    /**
     * @inheritDoc
     */
    public function get(string $key): Promise
    {
        return new Success($this->storage->get($key));
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): Promise
    {
        return new Success($this->storage->has($key));
    }

    /**
     * @inheritDoc
     */
    public function remove(string $key): Promise
    {
        $this->storage->remove($key);

        return new Success(true);
    }

    /**
     * @inheritDoc
     */
    public function save(string $key, $value, int $ttl = 0): Promise
    {
        /** @psalm-suppress MixedArgument */
        $this->storage->push($key, $value, $ttl);

        return new Success(true);
    }

    /**
     * @inheritDoc
     */
    public function clear(): Promise
    {
        $this->storage->clear();

        return new Success(true);
    }

    public function __construct()
    {
        $this->storage = InMemoryStorage::instance();
    }
}
