<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Repository;

use Desperado\ConcurrencyFramework\Domain\EventSourced\AggregateRootInterface;
use Desperado\ConcurrencyFramework\Domain\EventStore\EventStoreInterface;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Domain\Repository\AggregateRepositoryInterface;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Aggregate\AbstractAggregateRoot;

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
