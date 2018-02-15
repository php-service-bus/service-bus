<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Store;

use Desperado\ServiceBus\AbstractSaga;
use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;
use Desperado\ServiceBus\Saga\Serializer\SagaSerializerInterface;
use Desperado\ServiceBus\Saga\Storage\SagaStorageInterface;
use Desperado\ServiceBus\Saga\Storage\StoredSaga;
use Desperado\ServiceBus\Saga\Store\Exceptions as StoreExceptions;
use Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationException;

/**
 * Saga store
 */
class SagaStore
{
    /**
     * Saga storage backend
     *
     * @var SagaStorageInterface
     */
    private $storage;

    /**
     * Saga serializer
     *
     * @var SagaSerializerInterface
     */
    private $sagaSerializer;

    /**
     * @param SagaStorageInterface    $storage
     * @param SagaSerializerInterface $sagaSerializer
     */
    public function __construct(SagaStorageInterface $storage, SagaSerializerInterface $sagaSerializer)
    {
        $this->storage = $storage;
        $this->sagaSerializer = $sagaSerializer;
    }

    /**
     * Load saga
     *
     * @param AbstractSagaIdentifier $id
     *
     * @return AbstractSaga|null
     *
     * @throws StoreExceptions\LoadSagaFailedException
     */
    public function load(AbstractSagaIdentifier $id): ?AbstractSaga
    {
        try
        {
            $storedSaga = $this->storage->load($id);

            if(null !== $storedSaga)
            {
                return $this->sagaSerializer->unserialize($storedSaga->getPayload());
            }

            return null;
        }
        catch(\Throwable $throwable)
        {
            throw new StoreExceptions\LoadSagaFailedException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * Save saga
     *
     * @param AbstractSaga $saga
     * @param bool         $isNew
     *
     * @return void
     *
     * @throws StoreExceptions\DuplicateSagaException
     * @throws StoreExceptions\SagaStoreException
     */
    public function save(AbstractSaga $saga, bool $isNew): void
    {
        try
        {
            $storedSaga = StoredSaga::create(
                $saga->getId(),
                $this->sagaSerializer->serialize($saga),
                $saga->getState()->getStatusCode(),
                $saga->getCreatedAt(),
                $saga->getClosedAt()
            );

            true === $isNew
                ? $this->storage->save($storedSaga)
                : $this->storage->update($storedSaga);
        }
        catch(UniqueConstraintViolationException $exception)
        {
            throw new StoreExceptions\DuplicateSagaException($saga->getId());
        }
        catch(\Throwable $throwable)
        {
            throw new StoreExceptions\SagaStoreException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * Delete saga
     *
     * @param AbstractSagaIdentifier $id
     *
     * @return void
     *
     * @throws StoreExceptions\RemoveSagaFailedException
     */
    public function remove(AbstractSagaIdentifier $id): void
    {
        try
        {
            $this->storage->remove($id);
        }
        catch(\Throwable $throwable)
        {
            throw new StoreExceptions\RemoveSagaFailedException($throwable->getMessage(), 0, $throwable);
        }
    }
}
