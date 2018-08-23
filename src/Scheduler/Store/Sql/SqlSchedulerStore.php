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

namespace Desperado\ServiceBus\Scheduler\Store\Sql;

use function Amp\call;
use Amp\Promise;
use Amp\Success;
use Desperado\ServiceBus\Scheduler\Store\SchedulerRegistry;
use Desperado\ServiceBus\Scheduler\Store\SchedulerStore;
use function Desperado\ServiceBus\Storage\fetchOne;
use function Desperado\ServiceBus\Storage\SQL\createInsertQuery;
use function Desperado\ServiceBus\Storage\SQL\equalsCriteria;
use function Desperado\ServiceBus\Storage\SQL\selectQuery;
use function Desperado\ServiceBus\Storage\SQL\updateQuery;
use Desperado\ServiceBus\Storage\StorageAdapter;

/**
 *
 */
final class SqlSchedulerStore implements SchedulerStore
{
    /**
     * @var StorageAdapter
     */
    private $adapter;

    /**
     * @param StorageAdapter $adapter
     */
    public function __construct(StorageAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @inheritDoc
     */
    public function load(string $id): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(string $id) use ($adapter): \Generator
            {
                $registry = null;

                $query = selectQuery('scheduler_registry')
                    ->where(equalsCriteria('id', $id))
                    ->compile();

                /** @var array|null $row */
                $row = yield fetchOne(
                    yield $adapter->execute($query->sql(), $query->params())
                );

                if(true === \is_array($row) && 0 !== \count($row))
                {
                    $row['payload'] = $adapter->unescapeBinary($row['payload']);

                    $registry = \unserialize(\base64_decode($row['payload']), ['allowed_classes' => true]);
                }

                return yield new Success($registry);
            },
            $id
        );
    }

    /**
     * @inheritDoc
     */
    public function add(SchedulerRegistry $registry): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(SchedulerRegistry $registry) use ($adapter): \Generator
            {
                $query = createInsertQuery(
                    'scheduler_registry',
                    ['id' => $registry->id(), 'payload' => \base64_encode(\serialize($registry))]
                )->compile();

                yield $adapter->execute($query->sql(), $query->params());
            },
            $registry
        );
    }


    /**
     * @inheritDoc
     */
    public function update(SchedulerRegistry $registry): Promise
    {
        $adapter = $this->adapter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(SchedulerRegistry $registry) use ($adapter): \Generator
            {
                $query = updateQuery('scheduler_registry', ['payload' => \base64_encode(\serialize($registry))])
                    ->where(equalsCriteria('id', $registry->id()))
                    ->compile();

                yield $adapter->execute($query->sql(), $query->params());
            },
            $registry
        );
    }
}
