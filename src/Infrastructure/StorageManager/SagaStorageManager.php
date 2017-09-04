<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\StorageManager;

use Desperado\Framework\Domain\Event\DomainEvent;
use Desperado\Framework\Domain\Identity\IdentityInterface;
use Desperado\Framework\Domain\Messages\CommandInterface;
use Desperado\Framework\Domain\Repository\SagaRepositoryInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryOptions;
use Desperado\Framework\Infrastructure\EventSourcing\Saga\AbstractSaga;

/**
 * Saga manager
 */
class SagaStorageManager implements SagaStorageManagerInterface
{
    private $sagaNamespace;

    /**
     * Saga repository
     *
     * @var SagaRepositoryInterface
     */
    private $sagaRepository;

    /**
     * Persist sagas queue
     *
     * @var \SplDoublyLinkedList
     */
    private $persistQueue;

    /**
     * @param string                  $sagaNamespace
     * @param SagaRepositoryInterface $sagaRepository
     */
    public function __construct(
        string $sagaNamespace,
        SagaRepositoryInterface $sagaRepository
    )
    {
        $this->sagaNamespace = $sagaNamespace;
        $this->sagaRepository = $sagaRepository;

        $this->persistQueue = new \SplDoublyLinkedList();
        $this->persistQueue->setIteratorMode(
            \SplDoublyLinkedList::IT_MODE_FIFO |
            \SplDoublyLinkedList::IT_MODE_DELETE
        );
    }

    /**
     * @inheritdoc
     */
    public function getSagaNamespace(): string
    {
        return $this->sagaNamespace;
    }

    /**
     * @inheritdoc
     */
    public function persist(AbstractSaga $saga): void
    {
        if(false === $this->persistQueue->offsetExists($saga->getId()->toCompositeIndexHash()))
        {
            $this->persistQueue->add($saga->getId()->toCompositeIndexHash(), $saga);
        }
    }

    /**
     * @inheritdoc
     */
    public function load(IdentityInterface $identity, callable $onLoaded, callable $onFailed = null): void
    {
        if(true === $this->persistQueue->offsetExists($identity->toCompositeIndexHash()))
        {
            $onLoaded($this->persistQueue->offsetGet($identity->toCompositeIndexHash()));
        }
        else
        {
            $this->sagaRepository->load(
                $identity,
                $this->sagaNamespace,
                function(AbstractSaga $saga = null) use ($onLoaded)
                {
                    if(null !== $saga)
                    {
                        if(false === $this->persistQueue->offsetExists($saga->getId()->toCompositeIndexHash()))
                        {
                            $this->persistQueue->add($saga->getId()->toCompositeIndexHash(), $saga);
                        }

                        $onLoaded($saga);
                    }
                },
                $onFailed
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function commit(DeliveryContextInterface $context, callable $onComplete = null, callable $onFailed = null): void
    {
        try
        {
            $deliveryOptions = new DeliveryOptions();
            $queue = clone $this->persistQueue;

            while(false === $this->persistQueue->isEmpty())
            {
                /** @var AbstractSaga $aggregateRoot */
                $saga = $this->persistQueue->shift();

                $this->sagaRepository->save(
                    $saga,
                    function() use ($saga, $context, $deliveryOptions)
                    {
                        foreach($saga->getToPublishEvents() as $event)
                        {
                            /** @var DomainEvent $domainEvent */
                            $context->publish($event, $deliveryOptions);
                        }

                        foreach($saga->getCommands() as $command)
                        {
                            /** @var CommandInterface $command */
                            $context->send($command, $deliveryOptions);
                        }
                    },
                    function(\Throwable $throwable)
                    {
                        throw $throwable;
                    }
                );

                $this->persistQueue->offsetUnset($aggregateRoot->getId()->toCompositeIndexHash());
            }

            $this->persistQueue = $queue;

            if(null !== $onComplete)
            {
                $onComplete();
            }
        }
        catch(\Throwable $throwable)
        {
            if(null !== $onFailed)
            {
                $onFailed($throwable);
            }
        }
    }
}
