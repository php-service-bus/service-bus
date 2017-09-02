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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga;

use Desperado\ConcurrencyFramework\Domain\DateTime;
use Desperado\ConcurrencyFramework\Domain\EventSourced\SagaInterface;
use Desperado\ConcurrencyFramework\Domain\EventSourced\SagaStateInterface;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\AbstractEventSourced;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Contract\EventSourcedEntryCreatedEvent;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga\Contract\SagaCompletedEvent;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga\Contract\SagaExpiredEvent;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga\Contract\SagaFailedEvent;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga\Contract\SagaInitializedEvent;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Saga\Exceptions\SagaIsClosedException;

/**
 * Base saga class
 */
abstract class AbstractSaga extends AbstractEventSourced implements SagaInterface
{
    /**
     * Fired commands collection
     *
     * @var array
     */
    private $commands = [];

    /**
     * Saga state
     *
     * @var SagaState
     */
    private $state;

    /**
     * @inheritdoc
     */
    public function resetCommands(): void
    {
        $this->commands = [];
    }

    /**
     * @inheritdoc
     */
    public function getCommands(): array
    {
        $commands = $this->commands;

        $this->resetCommands();

        return $commands;
    }

    /**
     * Execute event (for saga listeners only. Don't use this method)
     *
     * @param EventInterface $event
     *
     * @return void
     */
    public function transition(EventInterface $event): void
    {
        $this->raiseEvent($event, false);
    }

    /**
     * @inheritdoc
     */
    public function getState(): SagaStateInterface
    {
        return $this->state;
    }

    /**
     * Saga initialized callback
     *
     * @return void
     */
    protected function onInitialized(): void
    {

    }

    /**
     * Create new saga
     *
     * @param IdentityInterface $identity
     *
     * @return $this
     */
    final protected static function new(IdentityInterface $identity): self
    {
        $self = new static();
        $eventSourcedCreated = new EventSourcedEntryCreatedEvent();
        $eventSourcedCreated->id = $identity->toString();
        $eventSourcedCreated->type = \get_class($identity);
        $eventSourcedCreated->createdAt = DateTime::nowToString();

        $sagaInitialized = new SagaInitializedEvent();
        $sagaInitialized->createdAt = DateTime::nowToString();
        $sagaInitialized->expireDate = DateTime::fromString($self->getExpirePeriod())->toString();

        $self->raiseEvent($eventSourcedCreated);
        $self->raiseEvent($sagaInitialized);

        return $self;
    }

    /**
     * Fire command
     *
     * @param CommandInterface $command
     *
     * @return void
     */
    final protected function fire(CommandInterface $command): void
    {
        $this->assertIsProcessing();

        $this->commands[] = $command;
    }

    /**
     * Mark saga as expired
     *
     * @return void
     */
    final protected function expire(): void
    {
        $this->assertIsProcessing();

        $expiredEvent = new SagaExpiredEvent();
        $expiredEvent->expiredAt = DateTime::nowToString();;

        $this->raiseEvent($expiredEvent);
    }

    /**
     * Mark saga as successful completed
     *
     * @return void
     */
    final protected function complete(): void
    {
        $this->assertIsProcessing();

        $completedEvent = new SagaCompletedEvent();
        $completedEvent->closedAt = DateTime::nowToString();;

        $this->raiseEvent($completedEvent);
    }

    /**
     * Mark saga as failed
     *
     * @param string $message
     *
     * @return void
     */
    final protected function fail(string $message): void
    {
        $this->assertIsProcessing();

        $failedEvent = new SagaFailedEvent();
        $failedEvent->closedAt = DateTime::nowToString();;
        $failedEvent->reason = $message;

        $this->raiseEvent($failedEvent);
    }

    /**
     * Saga failed
     *
     * @param SagaFailedEvent $event
     *
     * @return void
     */
    final protected function onSagaFailedEvent(SagaFailedEvent $event): void
    {
        $this->state = $this->state->fail(
            $event->reason,
            DateTime::fromString($event->closedAt)
        );
    }

    /**
     * Saga successful completed
     *
     * @param SagaCompletedEvent $event
     *
     * @return void
     */
    final protected function onSagaCompletedEvent(SagaCompletedEvent $event): void
    {
        $this->state = $this->state->complete(
            DateTime::fromString($event->closedAt)
        );
    }

    /**
     * Saga expired
     *
     * @param SagaExpiredEvent $event
     *
     * @return void
     */
    final protected function onSagaExpiredEvent(SagaExpiredEvent $event): void
    {
        $this->state = $this->state->expire(
            DateTime::fromString($event->expiredAt)
        );
    }

    /**
     * Saga initialized
     *
     * @param SagaInitializedEvent $event
     *
     * @return void
     */
    final protected function onSagaInitializedEvent(SagaInitializedEvent $event): void
    {
        $this->state = SagaState::create(
            DateTime::fromString($event->createdAt),
            DateTime::fromString($event->expireDate)
        );

        $this->onInitialized();
    }

    /**
     * Get expired period
     *
     * @return string
     */
    protected function getExpirePeriod(): string
    {
        return '+1 hour';
    }

    /**
     * Assert saga is not closed
     *
     * @return void
     *
     * @throws SagaIsClosedException
     */
    private function assertIsProcessing(): void
    {
        if(true === $this->state->isClosed())
        {
            throw new SagaIsClosedException(
                \sprintf('Saga "%s" is closed with status "%s"', $this->getId(), $this->state->getStatus())
            );
        }
    }
}
