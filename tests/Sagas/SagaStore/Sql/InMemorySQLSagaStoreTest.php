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

namespace Desperado\ServiceBus\Tests\Sagas\SagaStore\Sql;


use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapter;
use Desperado\ServiceBus\Infrastructure\Storage\StorageAdapterFactory;

/**
 *
 */
final class InMemorySQLSagaStoreTest extends SQLSagaStoreTestCase
{
    /**
     * @inheritDoc
     */
    protected function adapter(): StorageAdapter
    {
        return StorageAdapterFactory::inMemory();
    }

}
