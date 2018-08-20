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

namespace Desperado\ServiceBus\Tests\Sagas\Configuration\Stubs;

use Amp\Promise;
use Desperado\ServiceBus\Sagas\SagaId;
use Desperado\ServiceBus\Sagas\SagaStore\SagasStore;
use Desperado\ServiceBus\Sagas\SagaStore\StoredSaga;

/**
 *
 */
class TestSagaStoreImplementation implements SagasStore
{
    public function save(StoredSaga $savedSaga, callable $afterSaveHandler): Promise
    {

    }

    public function update(StoredSaga $savedSaga, callable $afterSaveHandler): Promise
    {

    }

    public function load(SagaId $id): Promise
    {

    }

    public function remove(SagaId $id): Promise
    {

    }

}
