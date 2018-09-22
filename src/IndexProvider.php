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
use Amp\Success;
use Desperado\ServiceBus\Index\IndexKey;
use Desperado\ServiceBus\Index\Storage\IndexesStorage;
use Desperado\ServiceBus\Index\IndexValue;
use Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationCheckFailed;

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
     * @param IndexKey $indexKey
     *
     * @return Promise<\Desperado\ServiceBus\Index\IndexValue|null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function get(IndexKey $indexKey): Promise
    {
        $storage = $this->storage;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(IndexKey $indexKey) use ($storage): \Generator
            {
                /** @var mixed $value */
                $value = yield $storage->find(
                    $indexKey->indexName(),
                    $indexKey->valueKey()
                );

                if(null !== $value && true === \is_scalar($value))
                {
                    return yield new Success(IndexValue::create($value));
                }
            },
            $indexKey
        );
    }

    /**
     * Is there a value in the index
     *
     * @param IndexKey $indexKey
     *
     * @return Promise<bool>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function has(IndexKey $indexKey): Promise
    {
        $storage = $this->storage;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(IndexKey $indexKey) use ($storage): \Generator
            {
                /** @var mixed $value */
                $value = yield $storage->find(
                    $indexKey->indexName(),
                    $indexKey->valueKey()
                );

                if(null !== $value && true === \is_scalar($value))
                {
                    return yield new Success(true);
                }

                return yield new Success(false);
            },
            $indexKey
        );
    }

    /**
     * Add a value to the index. If false, then the value already exists
     *
     * @param IndexKey   $indexKey
     * @param IndexValue $value
     *
     * @return Promise<bool>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
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
                    yield $storage->add(
                        $indexKey->indexName(),
                        $indexKey->valueKey(),
                        $value->value()
                    );

                    return yield new Success(true);
                }
                catch(UniqueConstraintViolationCheckFailed $exception)
                {
                    return yield new Success(false);
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
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
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
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    public function update(IndexKey $indexKey, IndexValue $value): Promise
    {
        return $this->storage->update($indexKey->indexName(), $indexKey->valueKey(), $value->value());
    }
}
