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
class SagaStorageManager implements SagaStorageManagerInterface
{
    private $sagaNamespace;

    /**
     * Saga repository
     *
     * @var SagaRepositoryInterface
     */
    private $sagaRepository;

    /**
     * Persist sagas queue
     *
     * @var \SplObjectStorage
     */
    private $persistQueue;

    /**
     * @param string                  $sagaNamespace
     * @param SagaRepositoryInterface $sagaRepository
     */
    public function __construct(
        string $sagaNamespace,
        SagaRepositoryInterface $sagaRepository
    )
    {
        $this->sagaNamespace = $sagaNamespace;
        $this->sagaRepository = $sagaRepository;

        $this->persistQueue = new \SplObjectStorage();
    }

    /**
     * @inheritdoc
     */
    public function getSagaNamespace(): string
    {
        return $this->sagaNamespace;
    }

    /**
     * @inheritdoc
     */
    public function persist(AbstractSaga $saga): void
    {
        if(false === $this->persistQueue->contains($saga))
        {
            $this->persistQueue->attach($saga);
        }
    }

    /**
     * @inheritdoc
     */
    public function load(IdentityInterface $identity, callable $onLoaded, callable $onFailed = null): void
    {
        $this->sagaRepository->load(
            $identity,
            $this->sagaNamespace,
            function(AbstractSaga $saga = null) use ($onLoaded)
            {
                if(null !== $saga)
                {
                    $saga->resetCommands();
                    $saga->resetUncommittedEvents();
                    $saga->resetToPublishEvents();

                    $this->persist($saga);
                }

                $onLoaded($saga);
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
                /** @var AbstractSaga $saga */
                $saga = $this->persistQueue->current();

                $this->sagaRepository->save(
                    $saga,
                    function() use ($saga, $context, $deliveryOptions)
                    {
                        $this->persistQueue->detach($saga);

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
