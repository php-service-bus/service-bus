<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus;

use Desperado\Domain\DateTime;
use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\ServiceBus\Saga\Exceptions\SagaIsClosedException;
use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;
use Desperado\ServiceBus\Saga\Metadata\SagaMetadata;
use Desperado\ServiceBus\Saga\SagaState;
use Desperado\ServiceBus\Sagas\Events\SagaCreatedEvent;
use Desperado\ServiceBus\Sagas\Events\SagaStatusWasChangedEvent;

/**
 * Base saga class
 *
 * @api
 */
abstract class AbstractSaga
{
    private const EVENT_APPLY_PREFIX = 'on';
    private const DEFAULT_EXPIRATION_MODIFIER = '+1 hour';

    /**
     * Saga identity
     *
     * @var AbstractSagaIdentifier
     */
    private $id;

    /**
     * List of events that should be published while saving
     *
     * @var AbstractEvent[]
     */
    private $events;

    /**
     * List of commands that should be fired while saving
     *
     * @var AbstractCommand[]
     */
    private $commands;

    /**
     * Current saga state
     *
     * @var SagaState
     */
    private $state;

    /**
     * Expire date modifier
     *
     * @var string
     */
    private $expireDateModifier;

    /**
     * @param AbstractSagaIdentifier $identity
     * @param SagaMetadata           $metadata
     */
    final public function __construct(AbstractSagaIdentifier $identity, SagaMetadata $metadata)
    {
        $this->expireDateModifier = $metadata->getExpireDateModifier();
        $this->events = [];
        $this->commands = [];

        $sagaInitialized = SagaCreatedEvent::create([
                'id'                  => $identity->toString(),
                'identifierNamespace' => $identity->getIdentityClassNamespace(),
                'sagaNamespace'       => \get_class($this),
                'createdAt'           => DateTime::nowToString(),
                'expireDate'          => DateTime::fromString($this->getExpirePeriod())->toString()
            ]
        );

        $this->raiseEvent($sagaInitialized);
    }

    /**
     * Start new saga
     *
     * @param AbstractCommand $command
     *
     * @return void
     */
    abstract public function start(AbstractCommand $command): void;

    /**
     * @inheritdoc
     */
    final public function transition(AbstractEvent $event): void
    {
        $this->assertIsProcessing();

        $this->raiseEvent($event, false);
    }

    /**
     * Get saga identifier
     *
     * @return AbstractSagaIdentifier
     */
    final public function getId(): AbstractSagaIdentifier
    {
        return $this->id;
    }

    /**
     * Get saga identifier as string
     *
     * @return string
     */
    final public function getIdentityAsString(): string
    {
        return $this->id->toString();
    }

    /**
     * Get a list of commands that should be fired while saving
     *
     * AbstractCommand[]
     *
     * @return \Iterator
     */
    final public function getCommands(): \Iterator
    {
        $commands = $this->commands;

        $this->commands = [];

        return new \ArrayIterator($commands);
    }

    /**
     * Get a list of events that should be published while saving.
     *
     * AbstractEvent[]
     *
     * @return \Iterator
     */
    final public function getEvents(): \Iterator
    {
        $events = $this->events;

        $this->events = [];

        return new \ArrayIterator($events);
    }

    /**
     * Get the current state of the saga
     *
     * @return SagaState
     */
    final public function getState(): SagaState
    {
        return $this->state;
    }

    /**
     * Get the date of creation of the saga
     *
     * @return DateTime
     */
    final public function getCreatedAt(): DateTime
    {
        return $this->getState()->getCreatedAt();
    }

    /**
     * Get the closing date of the saga
     *
     * @return DateTime|null
     */
    final public function getClosedAt(): ?DateTime
    {
        return $this->getState()->getClosedAt();
    }

    /**
     * Flush commands/events
     *
     * @return void
     */
    final public function __wakeup(): void
    {
        $this->events = $this->commands = [];
    }

    /**
     * @codeCoverageIgnore
     *
     * The method is called when the saga is initialized
     * Can be redefined if necessary
     *
     * @return void
     */
    protected function onInitialized(): void
    {

    }

    /**
     * Raise event
     *
     * @param AbstractEvent $event
     * @param bool          $publishOnFlush
     *
     * @return void
     */
    final protected function raiseEvent(AbstractEvent $event, bool $publishOnFlush = true): void
    {
        $this->applyEvent($event);

        if(true === $publishOnFlush)
        {
            $this->events[] = $event;
        }
    }

    /**
     * Fire command
     *
     * @param AbstractCommand $command
     *
     * @return void
     */
    final protected function fire(AbstractCommand $command): void
    {
        $this->assertIsProcessing();

        $this->commands[] = $command;
    }

    /**
     * Change the status of the expiry of the life
     *
     * @return void
     */
    final protected function expire(): void
    {
        $this->assertIsProcessing();

        $changedStatusEvent = SagaStatusWasChangedEvent::create([
            'id'                  => $this->getIdentityAsString(),
            'identifierNamespace' => $this->getId()->getIdentityClassNamespace(),
            'sagaNamespace'       => \get_class($this),
            'previousStatusId'    => $this->getState()->getStatusCode(),
            'newStatusId'         => SagaState::STATUS_EXPIRED,
            'datetime'            => DateTime::nowToString()
        ]);

        $this->raiseEvent($changedStatusEvent);
    }

    /**
     * Change status to successfully completed
     *
     * @param string|null $message
     *
     * @return void
     *
     * @throws SagaIsClosedException
     */
    final protected function complete(?string $message = null): void
    {
        $this->assertIsProcessing();

        $changedStatusEvent = SagaStatusWasChangedEvent::create([
            'id'                  => $this->getIdentityAsString(),
            'identifierNamespace' => $this->getId()->getIdentityClassNamespace(),
            'sagaNamespace'       => \get_class($this),
            'previousStatusId'    => $this->getState()->getStatusCode(),
            'newStatusId'         => SagaState::STATUS_COMPLETED,
            'datetime'            => DateTime::nowToString(),
            'description'         => $message
        ]);

        $this->raiseEvent($changedStatusEvent);
    }

    /**
     * Change status to completed with an error
     *
     * @param string $message
     *
     * @return void
     *
     * @throws SagaIsClosedException
     */
    final protected function fail(string $message): void
    {
        $this->assertIsProcessing();

        $changedStatusEvent = SagaStatusWasChangedEvent::create([
            'id'                  => $this->getIdentityAsString(),
            'identifierNamespace' => $this->getId()->getIdentityClassNamespace(),
            'sagaNamespace'       => \get_class($this),
            'previousStatusId'    => $this->getState()->getStatusCode(),
            'newStatusId'         => SagaState::STATUS_FAILED,
            'datetime'            => DateTime::nowToString(),
            'description'         => $message
        ]);


        $this->raiseEvent($changedStatusEvent);
    }

    /**
     * Handling the creation of the saga
     *
     * @param SagaCreatedEvent $event
     *
     * @return void
     */
    final protected function onSagaCreatedEvent(SagaCreatedEvent $event): void
    {
        $identifierNamespace = $event->getIdentifierNamespace();

        $this->id = new $identifierNamespace($event->getId(), \get_class($this));
        $this->state = SagaState::create(
            DateTime::fromString($event->getCreatedAt()),
            DateTime::fromString($event->getExpireDate())
        );
    }

    /**
     * Processing of the saga status change event
     *
     * @param SagaStatusWasChangedEvent $event
     *
     * @return void
     */
    final protected function onSagaStatusWasChangedEvent(SagaStatusWasChangedEvent $event): void
    {
        switch($event->getNewStatusId())
        {
            case SagaState::STATUS_FAILED:
                $this->state = $this->state->fail(
                    (string) $event->getDescription(),
                    DateTime::fromString($event->getDatetime())
                );
                break;

            case SagaState::STATUS_COMPLETED:
                $this->state = $this->state->complete(
                    DateTime::fromString($event->getDatetime()),
                    (string) $event->getDescription()
                );
                break;

            case SagaState::STATUS_EXPIRED:
                $this->state = $this->state->expire(
                    DateTime::fromString($event->getDatetime())
                );
                break;
        }
    }

    /**
     * Getting the saga life modifier
     *
     * @return string
     */
    private function getExpirePeriod(): string
    {
        return '' !== $this->expireDateModifier
            ? $this->expireDateModifier
            : self::DEFAULT_EXPIRATION_MODIFIER;
    }

    /**
     * Apply event
     *
     * @param AbstractEvent $event
     *
     * @return void
     */
    private function applyEvent(AbstractEvent $event): void
    {
        $eventListenerMethodNameParts = \explode('\\', \get_class($event));
        $eventListenerMethodName = \sprintf(
            '%s%s',
            self::EVENT_APPLY_PREFIX,
            \end($eventListenerMethodNameParts)
        );

        if(true === \method_exists($this, $eventListenerMethodName))
        {
            $closure = function(AbstractEvent $event) use ($eventListenerMethodName)
            {
                $this->{$eventListenerMethodName}($event);
            };

            $closure->call($this, $event);
        }
    }

    /**
     * Make sure that the saga is not closed
     *
     * @return void
     *
     * @throws SagaIsClosedException
     */
    private function assertIsProcessing(): void
    {
        if(true === $this->getState()->isClosed())
        {
            throw new SagaIsClosedException(
                $this->getId(),
                $this->getState()->getStatusCode()
            );
        }
    }
}
