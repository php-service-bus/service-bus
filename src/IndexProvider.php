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

namespace Desperado\ServiceBus;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Index\IndexKey;
use Desperado\ServiceBus\Index\Storage\IndexesStorage;
use Desperado\ServiceBus\Index\IndexValue;
use Desperado\ServiceBus\Infrastructure\Storage\Exceptions\UniqueConstraintViolationCheckFailed;

/**
 * Indexes support
 */
final class IndexProvider
{
    /**
     * @var IndexesStorage
     */
    private $storage;

    /**
     * @param IndexesStorage $storage
     */
    public function __construct(IndexesStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Receive index value
     *
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param IndexKey $indexKey
     *
     * @return Promise<\Desperado\ServiceBus\Index\IndexValue|null>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function get(IndexKey $indexKey): Promise
    {
        $storage = $this->storage;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(IndexKey $indexKey) use ($storage): \Generator
            {
                /** @var string|int|float|boolean|null $value */
                $value = yield $storage->find($indexKey->indexName(), $indexKey->valueKey());

                if(true === \is_scalar($value))
                {
                    return IndexValue::create($value);
                }
            },
            $indexKey
        );
    }

    /**
     * Is there a value in the index
     *
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param IndexKey $indexKey
     *
     * @return Promise<bool>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function has(IndexKey $indexKey): Promise
    {
        $storage = $this->storage;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(IndexKey $indexKey) use ($storage): \Generator
            {
                /** @var string|int|float|boolean|null $value */
                $value = yield $storage->find($indexKey->indexName(), $indexKey->valueKey());

                return true === \is_scalar($value);
            },
            $indexKey
        );
    }

    /**
     * Add a value to the index. If false, then the value already exists
     *
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param IndexKey   $indexKey
     * @param IndexValue $value
     *
     * @return Promise<bool>
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function add(IndexKey $indexKey, IndexValue $value): Promise
    {
        $storage = $this->storage;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(IndexKey $indexKey, IndexValue $value) use ($storage): \Generator
            {
                try
                {
                    yield $storage->add($indexKey->indexName(), $indexKey->valueKey(), $value->value());

                    return true;
                }
                catch(UniqueConstraintViolationCheckFailed $exception)
                {
                    return false;
                }
            },
            $indexKey, $value
        );
    }

    /**
     * Remove value from index
     *
     * @param IndexKey $indexKey
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function remove(IndexKey $indexKey): Promise
    {
        return $this->storage->delete($indexKey->indexName(), $indexKey->valueKey());
    }

    /**
     * Update value in index
     *
     * @param IndexKey   $indexKey
     * @param IndexValue $value
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Infrastructure\Storage\Exceptions\StorageInteractingFailed
     */
    public function update(IndexKey $indexKey, IndexValue $value): Promise
    {
        return $this->storage->update($indexKey->indexName(), $indexKey->valueKey(), $value->value());
    }
}
