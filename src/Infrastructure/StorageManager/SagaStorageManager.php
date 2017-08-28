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

namespace Desperado\ConcurrencyFramework\Infrastructure\StorageManager;

use Desperado\ConcurrencyFramework\Domain\Event\DomainEvent;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Repository\SagaRepositoryInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryOptions;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga\AbstractSaga;

/**
 * Saga manager
 */
class SagaStorageManager extends AbstractStorageManager
{
    /**
     * Saga repository
     *
     * @var SagaRepositoryInterface
     */
    private $aggregateRepository;

    /**
     * @param string                  $sagaNamespace
     * @param SagaRepositoryInterface $sagaRepository
     */
    public function __construct(
        string $sagaNamespace,
        SagaRepositoryInterface $sagaRepository

    )
    {
        parent::__construct($sagaNamespace);

        $this->aggregateRepository = $sagaRepository;
    }

    /**
     * @inheritdoc
     *
     * @return AbstractSaga
     */
    public function load(IdentityInterface $identity)
    {
        /** @var AbstractSaga|null $saga */
        $saga = $this->aggregateRepository->load($identity, $this->getEntityNamespace());

        if(null !== $saga)
        {
            $this->getPersistMap()->attach($saga);
        }

        return $saga;
    }

    /**
     * @inheritdoc
     */
    public function commit(DeliveryContextInterface $context): void
    {
        $deliveryOptions = new DeliveryOptions();

        foreach($this->getPersistMap() as $saga)
        {
            /** @var AbstractSaga $saga */

            $eventStream = clone $saga->getEventStream();
            $commands = $saga->getCommands();

            $this->aggregateRepository->save($saga);

            foreach($eventStream as $domainEvent)
            {
                /** @var DomainEvent $domainEvent */
                $context->publish($domainEvent->getReceivedEvent(), $deliveryOptions);
            }

            foreach($commands as $command)
            {
                /** @var CommandInterface $command */
                $context->send($command, $deliveryOptions);
            }

            $saga->resetCommands();
            $saga->resetUncommittedEvents();

            $this->getPersistMap()->detach($saga);
        }

        $this->flushLocalStorage();
    }
}