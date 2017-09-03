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
class SagaStorageManager extends AbstractStorageManager
{
    /**
     * Saga repository
     *
     * @var SagaRepositoryInterface
     */
    private $sagaRepository;

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

        $this->sagaRepository = $sagaRepository;
    }

    /**
     * @inheritdoc
     *
     * @return AbstractSaga
     */
    public function load(IdentityInterface $identity)
    {
        /** @var AbstractSaga|null $saga */
        $saga = $this->sagaRepository->load($identity, $this->getEntityNamespace());

        if(null !== $saga)
        {
            if(false === $this->getPersistMap()->contains($saga))
            {
                $this->getPersistMap()->attach($saga);
            }
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

            $this->sagaRepository->save($saga);

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

            $this->getPersistMap()->detach($saga);
            $this->getRemoveMap()->detach($saga);
        }

        $this->flushLocalStorage();
    }

    /**
     * Get saga repository
     *
     * @return SagaRepositoryInterface
     */
    public function getSagaRepository(): SagaRepositoryInterface
    {
        return $this->sagaRepository;
    }
}
