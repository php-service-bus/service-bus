<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\UoW;

use Desperado\ServiceBus\Application\Context\ExecutionContextInterface;
use Desperado\ServiceBus\Saga\Exceptions\CommitSagaFailedException;
use Desperado\ServiceBus\Saga\Store\Exceptions\DuplicateSagaException;
use Desperado\ServiceBus\Saga\Store\SagaStore;

/**
 * Serves a set of sagas
 */
class UnitOfWork
{
    /**
     * Saga store
     *
     * @var SagaStore
     */
    private $sagaStore;

    /**
     * Sagas
     *
     * @var \SplObjectStorage
     */
    private $persistQueue;

    /**
     * @param SagaStore $sagaStore
     */
    public function __construct(SagaStore $sagaStore)
    {
        $this->sagaStore = $sagaStore;
        $this->persistQueue = new \SplObjectStorage();
    }

    /**
     * Persist saga
     *
     * @param ObservedSaga $observedSaga
     *
     * @return void
     */
    public function persist(ObservedSaga $observedSaga): void
    {
        if(false === $this->isPersisted($observedSaga))
        {
            $this->persistQueue->attach($observedSaga);
        }
    }

    /**
     * Is saga persisted
     *
     * @param ObservedSaga $observedSaga
     *
     * @return bool
     */
    public function isPersisted(ObservedSaga $observedSaga): bool
    {
        return $this->persistQueue->contains($observedSaga);
    }

    /**
     * Saving all sagas
     * Publication of events (if context specified)/send command
     *
     * @param ExecutionContextInterface|null $context
     *
     * @return void
     *
     * @throws DuplicateSagaException
     * @throws CommitSagaFailedException
     */
    public function commit(ExecutionContextInterface $context = null): void
    {
        $this->persistQueue->rewind();

        while(true === $this->persistQueue->valid())
        {
            /** @var ObservedSaga $observedSaga */
            $observedSaga = $this->persistQueue->current();

            $this->flush($observedSaga, $context);

            $this->persistQueue->next();
        }

        $this->persistQueue = new \SplObjectStorage();
    }

    /**
     * Flush saga
     *
     * @param ObservedSaga                   $observedSaga
     * @param ExecutionContextInterface|null $context
     *
     * @return void
     *
     * @throws DuplicateSagaException
     * @throws CommitSagaFailedException
     */
    private function flush(ObservedSaga $observedSaga, ExecutionContextInterface $context = null): void
    {
        try
        {
            $saga = $observedSaga->getSaga();

            $events = $saga->getEvents();
            $commands = $saga->getCommands();

            $this->sagaStore->save($saga, $observedSaga->isNew());

            if(null !== $context)
            {
                $this->publishEvents($events, $context);
                $this->sendEvents($commands, $context);
            }
        }
        catch(DuplicateSagaException $exception)
        {
            throw $exception;
        }
        catch(\Throwable $throwable)
        {
            throw new CommitSagaFailedException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * Publish all events
     *
     * @param \Iterator                      $events
     * @param ExecutionContextInterface $context
     *
     * @return void
     */
    private function publishEvents(\Iterator $events, ExecutionContextInterface $context): void
    {
        foreach($events as $event)
        {
            /** @var \Desperado\Domain\Message\AbstractEvent $event */

            $context->delivery($event);
        }
    }

    /**
     * Send all commands
     *
     * @param \Iterator                      $commands
     * @param ExecutionContextInterface $context
     *
     * @return void
     */
    private function sendEvents(\Iterator $commands, ExecutionContextInterface $context): void
    {
        foreach($commands as $command)
        {
            /** @var \Desperado\Domain\Message\AbstractCommand $command */

            $context->delivery($command);
        }
    }
}
