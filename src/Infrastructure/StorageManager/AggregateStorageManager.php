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

use Desperado\Framework\Domain\Identity\IdentityInterface;
use Desperado\Framework\Domain\Repository\AggregateRepositoryInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryOptions;
use Desperado\Framework\Infrastructure\EventSourcing\Aggregate\AbstractAggregateRoot;
use Desperado\Framework\Infrastructure\EventSourcing\Aggregate\Contract\AggregateEventStreamStored;

/**
 * Aggregate storage manager
 */
class AggregateStorageManager implements AggregateStorageManagerInterface
{
    /**
     * Aggregate namespace
     *
     * @var string
     */
    private $aggregateNamespace;

    /**
     * Aggregate repository
     *
     * @var AggregateRepositoryInterface
     */
    private $aggregateRepository;

    /**
     * Persist aggregates queue
     *
     * @var \SplDoublyLinkedList
     */
    private $persistQueue;

    /**
     * @param string                       $aggregateNamespace
     * @param AggregateRepositoryInterface $aggregateRepository
     */
    public function __construct(
        string $aggregateNamespace,
        AggregateRepositoryInterface $aggregateRepository
    )
    {
        $this->aggregateNamespace = $aggregateNamespace;
        $this->aggregateRepository = $aggregateRepository;

        $this->persistQueue = new \SplDoublyLinkedList();
        $this->persistQueue->setIteratorMode(
            \SplDoublyLinkedList::IT_MODE_FIFO |
            \SplDoublyLinkedList::IT_MODE_DELETE
        );
    }

    /**
     * @inheritdoc
     */
    public function getAggregateNamespace(): string
    {
        return $this->aggregateNamespace;
    }

    /**
     * @inheritdoc
     */
    public function persist(AbstractAggregateRoot $aggregateRoot): void
    {
        if(false === $this->persistQueue->offsetExists($aggregateRoot->getId()->toCompositeIndexHash()))
        {
            $this->persistQueue->add($aggregateRoot->getId()->toCompositeIndexHash(), $aggregateRoot);
        }
    }

    /**
     * @todo: snapshots
     *
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
            $this->aggregateRepository->load(
                $identity,
                $this->aggregateNamespace,
                function(AbstractAggregateRoot $aggregateRoot = null) use ($onLoaded)
                {
                    if(null !== $aggregateRoot)
                    {
                        if(false === $this->persistQueue->offsetExists($aggregateRoot->getId()->toCompositeIndexHash()))
                        {
                            $this->persistQueue->add($aggregateRoot->getId()->toCompositeIndexHash(), $aggregateRoot);
                        }

                        $onLoaded($aggregateRoot);
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
                /** @var AbstractAggregateRoot $aggregateRoot */
                $aggregateRoot = $this->persistQueue->shift();

                $this->aggregateRepository->save(
                    $aggregateRoot,
                    function() use ($aggregateRoot, $deliveryOptions, $context)
                    {
                        $savedAggregateEvent = new AggregateEventStreamStored();
                        $savedAggregateEvent->id = $aggregateRoot->getId()->toString();
                        $savedAggregateEvent->type = \get_class($aggregateRoot->getId());
                        $savedAggregateEvent->aggregate = \get_class($aggregateRoot);
                        $savedAggregateEvent->version = $aggregateRoot->getVersion();

                        $context->publish($savedAggregateEvent, $deliveryOptions);

                        $aggregateRoot->resetUncommittedEvents();

                        foreach($aggregateRoot->getToPublishEvents() as $event)
                        {
                            $context->publish($event, $deliveryOptions);
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
