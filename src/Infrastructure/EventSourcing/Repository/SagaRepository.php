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

use Desperado\Framework\Domain\Event\DomainEventStream;
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
    public function load(
        IdentityInterface $identity,
        string $sagaNamespace,
        callable $onLoaded,
        callable $onFailed = null
    ): void
    {
        $this->eventStore->load(
            $identity,
            function(DomainEventStream $eventStream = null) use ($onLoaded, $onFailed, $identity, $sagaNamespace)
            {
                try
                {
                    $result = null;

                    if(null !== $eventStream)
                    {
                        $result = \call_user_func_array(
                            \sprintf('%s::fromEventStream', $sagaNamespace),
                            [$identity, $eventStream]
                        );
                    }

                    $onLoaded($result);
                }
                catch(\Throwable $throwable)
                {
                    if(null !== $onFailed)
                    {
                        $onFailed($throwable);
                    }
                }
            },
            $onFailed
        );
    }

    /**
     * @inheritdoc
     */
    public function save(SagaInterface $saga, callable $onSaved = null, callable $onFailed = null): void
    {
        /** @var AbstractSaga $saga */

        $this->eventStore->append(
            $saga->getId(),
            $saga->getEventStream(),
            $onSaved,
            $onFailed
        );
    }

    /**
     * @inheritdoc
     */
    public function remove(IdentityInterface $identity, callable $onRemoved = null, callable $onFailed = null): void
    {
        throw new \LogicException('Not implemented');
    }
}
