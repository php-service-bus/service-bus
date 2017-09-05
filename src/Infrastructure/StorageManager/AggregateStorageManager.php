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
     * @var \SplObjectStorage
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

        $this->persistQueue = new \SplObjectStorage();
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
        if(false === $this->persistQueue->contains($aggregateRoot))
        {
            $this->persistQueue->attach($aggregateRoot);
        }
    }

    /**
     * @todo: snapshots
     *
     * @inheritdoc
     */
    public function load(IdentityInterface $identity, callable $onLoaded, callable $onFailed = null): void
    {
        $this->aggregateRepository->load(
            $identity,
            $this->aggregateNamespace,
            function(AbstractAggregateRoot $aggregateRoot = null) use ($onLoaded)
            {
                if(null !== $aggregateRoot)
                {
                    $this->persist($aggregateRoot);

                    $onLoaded($aggregateRoot);
                }
            },
            $onFailed
        );
    }

    /**
     * @inheritdoc
     */
    public function commit(DeliveryContextInterface $context, callable $onComplete = null, callable $onFailed = null): void
    {
        try
        {
            $deliveryOptions = new DeliveryOptions();

            $this->persistQueue->rewind();

            while($this->persistQueue->valid())
            {
                /** @var AbstractAggregateRoot $aggregateRoot */
                $aggregateRoot = $this->persistQueue->current();

                $this->aggregateRepository->save(
                    $aggregateRoot,
                    function() use ($aggregateRoot, $deliveryOptions, $context)
                    {
                        $this->persistQueue->detach($aggregateRoot);

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

                $this->persistQueue->next();
            }

            $this->persistQueue = new \SplObjectStorage();

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
