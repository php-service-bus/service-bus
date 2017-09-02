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

use Desperado\Framework\Domain\EventSourced\SagaInterface;
use Desperado\Framework\Domain\EventStore\EventStoreInterface;
use Desperado\Framework\Domain\Identity\IdentityInterface;
use Desperado\Framework\Domain\Repository\SagaRepositoryInterface;
use Desperado\Framework\Infrastructure\EventSourcing\Saga\AbstractSaga;

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
