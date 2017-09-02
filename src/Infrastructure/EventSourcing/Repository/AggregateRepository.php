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

namespace Desperado\Framework\Infrastructure\EventSourcing\Repository;

use Desperado\Framework\Domain\EventSourced\AggregateRootInterface;
use Desperado\Framework\Domain\EventStore\EventStoreInterface;
use Desperado\Framework\Domain\Identity\IdentityInterface;
use Desperado\Framework\Domain\Repository\AggregateRepositoryInterface;
use Desperado\Framework\Infrastructure\EventSourcing\Aggregate\AbstractAggregateRoot;

/**
 * Aggregate repository
 */
class AggregateRepository implements AggregateRepositoryInterface
{
    /**
     * @var EventStoreInterface
     */
    private $eventStore;

    /**
     * @param EventStoreInterface $eventStore
     */
    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    /**
     * @inheritdoc
     *
     * @return AbstractAggregateRoot
     */
    public function load(IdentityInterface $identity, string $aggregateNamespace): ?AggregateRootInterface
    {
        $eventStream = $this->eventStore->load($identity);

        if(null !== $eventStream)
        {
            return \call_user_func_array(
                \sprintf('%s::fromEventStream', $aggregateNamespace),
                [$identity, $eventStream]
            );
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function save(AggregateRootInterface $aggregateRoot): void
    {
        /** @var AbstractAggregateRoot $aggregateRoot */

        $this->eventStore->append(
            $aggregateRoot->getId(),
            $aggregateRoot->getEventStream()
        );

        $aggregateRoot->resetUncommittedEvents();
    }
}
