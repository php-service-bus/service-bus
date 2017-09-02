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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Repository;

use Desperado\ConcurrencyFramework\Domain\EventSourced\SagaInterface;
use Desperado\ConcurrencyFramework\Domain\EventStore\EventStoreInterface;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Domain\Repository\SagaRepositoryInterface;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga\AbstractSaga;

/**
 * Saga repository
 */
class SagaRepository implements SagaRepositoryInterface
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
     * @return AbstractSaga
     */
    public function load(IdentityInterface $identity, string $sagaNamespace): ?SagaInterface
    {
        $eventStream = $this->eventStore->load($identity);

        if(null !== $eventStream)
        {
            return \call_user_func_array(
                \sprintf('%s::fromEventStream', $sagaNamespace),
                [$identity, $eventStream]
            );
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function save(SagaInterface $saga): void
    {
        /** @var AbstractSaga $saga */

        $this->eventStore->append(
            $saga->getId(),
            $saga->getEventStream()
        );
    }

    /**
     * @inheritdoc
     */
    public function remove(IdentityInterface $identity): void
    {
        throw new \LogicException('Not implemented');
    }
}
