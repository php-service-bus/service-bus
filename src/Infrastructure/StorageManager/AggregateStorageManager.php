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

namespace Desperado\ConcurrencyFramework\Infrastructure\StorageManager;

use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Domain\Repository\AggregateRepositoryInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryOptions;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Aggregate\AbstractAggregateRoot;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Aggregate\Contract\AggregateEventStreamStored;

/**
 * Aggregate storage manager
 */
class AggregateStorageManager extends AbstractStorageManager
{
    /**
     * Aggregate repository
     *
     * @var AggregateRepositoryInterface
     */
    private $aggregateRepository;

    /**
     * @param string                       $aggregateNamespace
     * @param AggregateRepositoryInterface $aggregateRepository
     */
    public function __construct(
        string $aggregateNamespace,
        AggregateRepositoryInterface $aggregateRepository

    )
    {
        parent::__construct($aggregateNamespace);

        $this->aggregateRepository = $aggregateRepository;
    }

    /**
     * Load aggregate
     *
     * @todo: snapshots
     *
     * @param IdentityInterface $identity
     *
     * @return AbstractAggregateRoot|null
     */
    public function load(IdentityInterface $identity)
    {
        /** @var AbstractAggregateRoot|null $aggregate */
        $aggregate = $this->aggregateRepository->load($identity, $this->getEntityNamespace());

        if(null !== $aggregate)
        {
            $this->getPersistMap()->attach($aggregate);
        }

        return $aggregate;
    }

    /**
     * @todo: close stream implementation
     *
     * @inheritdoc
     */
    public function commit(DeliveryContextInterface $context): void
    {
        $deliveryOptions = new DeliveryOptions();

        foreach($this->getPersistMap() as $aggregate)
        {
            /** @var AbstractAggregateRoot $aggregate */

            $this->aggregateRepository->save($aggregate);

            $savedAggregateEvent = new AggregateEventStreamStored();
            $savedAggregateEvent->id = $aggregate->getId()->toString();
            $savedAggregateEvent->type = \get_class($aggregate->getId());
            $savedAggregateEvent->aggregate = \get_class($aggregate);
            $savedAggregateEvent->version = $aggregate->getVersion();

            $context->publish($savedAggregateEvent, $deliveryOptions);

            $aggregate->resetUncommittedEvents();

            foreach($aggregate->getToPublishEvents() as $event)
            {
                $context->publish($event, $deliveryOptions);
            }

            $this->getPersistMap()->detach($aggregate);

            unset($aggregate);
        }

        $this->flushLocalStorage();
    }
}
