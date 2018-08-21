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

namespace Desperado\ServiceBus\Tests\MessageBus\Stubs;

use Amp\Promise;
use Desperado\ServiceBus\Sagas\SagaId;
use Desperado\ServiceBus\Sagas\SagaStore\SagasStore;
use Desperado\ServiceBus\Sagas\SagaStore\StoredSaga;

/**
 *
 */
final class MessageBusTestingSagaStore implements SagasStore
{
    /**
     * @inheritdoc
     */
    public function save(StoredSaga $savedSaga, callable $afterSaveHandler): Promise
    {

    }

    /**
     * @inheritdoc
     */
    public function update(StoredSaga $savedSaga, callable $afterSaveHandler): Promise
    {

    }

    /**
     * @inheritdoc
     */
    public function load(SagaId $id): Promise
    {

    }

    /**
     * @inheritdoc
     */
    public function remove(SagaId $id): Promise
    {

    }

}
