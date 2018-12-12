<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Sagas;

use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use function Desperado\ServiceBus\Common\datetimeInstantiator;
use Desperado\ServiceBus\Sagas\Configuration\SagaMetadata;
use Desperado\ServiceBus\Sagas\Contract\SagaClosed;
use Desperado\ServiceBus\Sagas\Contract\SagaCreated;
use Desperado\ServiceBus\Sagas\Contract\SagaStatusChanged;
use Desperado\ServiceBus\Sagas\Exceptions\ChangeSagaStateFailed;
use Desperado\ServiceBus\Sagas\Exceptions\InvalidExpireDateInterval;
use Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier;

/**
 * Base class for all sagas
 */
abstract class Saga
{
    /**
     * The prefix from which all names of methods-listeners of events should begin
     *
     * @var string
     */
    public const EVENT_APPLY_PREFIX = 'on';

    /**
     * Saga identifier
     *
     * @var SagaId
     */
    private $id;

    /**
     * List of events that should be published while saving
     *
     * @var array<int, \Desperado\ServiceBus\Common\Contract\Messages\Event>
     */
    private $events;

    /**
     * List of commands that should be fired while saving
     *
     * @var array<int, \Desperado\ServiceBus\Common\Contract\Messages\Command>
     */
    private $commands;

    /**
     * SagaStatus of the saga
     *
     * @var SagaStatus
     */
    private $status;

    /**
     * Date of saga creation
     *
     * @var \DateTimeImmutable
     */
    private $createdAt;

    /**
     * Saga expiration date
     *
     * @var \DateTimeImmutable
     */
    private $expireDate;

    /**
     * Date of saga closed
     *
     * @var \DateTimeImmutable|null
     */
    private $closedAt;

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param SagaId                  $id
     * @param \DateTimeImmutable|null $expireDate
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\ChangeSagaStateFailed
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidExpireDateInterval
     */
    final public function __construct(SagaId $id, ?\DateTimeImmutable $expireDate = null)
    {
        $this->assertSagaClassEqualsWithId($id);
        $this->clear();

        /** @var \DateTimeImmutable $currentDatetime */
        $currentDatetime = datetimeInstantiator('NOW');

        /** @var \DateTimeImmutable $expireDate */
        $expireDate = $expireDate ?? datetimeInstantiator(SagaMetadata::DEFAULT_EXPIRE_INTERVAL);

        $this->assertExpirationDateIsCorrect($expireDate);

        $this->id     = $id;
        $this->status = SagaStatus::created();

        $this->createdAt  = $currentDatetime;
        $this->expireDate = $expireDate;

        $this->raise(SagaCreated::create($id, $currentDatetime, $expireDate));
    }

    /**
     * Flush commands/events on wakeup
     *
     * @return void
     */
    final public function __wakeup(): void
    {
        $this->clear();
    }

    /**
     * Start saga flow
     *
     * @param Command $command
     *
     * @return void
     */
    abstract public function start(Command $command): void;

    /**
     * Receive saga id
     *
     * @return SagaId
     */
    final public function id(): SagaId
    {
        return $this->id;
    }

    /**
     * Date of creation
     *
     * @return \DateTimeImmutable
     */
    final public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * Date of expiration
     *
     * @return \DateTimeImmutable
     */
    final public function expireDate(): \DateTimeImmutable
    {
        return $this->expireDate;
    }

    /**
     * Raise (apply event)
     *
     * @param Event $event
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\ChangeSagaStateFailed
     */
    final protected function raise(Event $event): void
    {
        $this->assertNotClosedSaga();

        $this->applyEvent($event);
        $this->attachEvent($event);
    }

    /**
     * Fire command
     *
     * @param Command $command
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\ChangeSagaStateFailed
     */
    final protected function fire(Command $command): void
    {
        $this->assertNotClosedSaga();

        $this->attachCommand($command);
    }

    /**
     * Change saga status to completed
     *
     * @see SagaStatus::STATUS_COMPLETED
     *
     * @param string|null $withReason
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\ChangeSagaStateFailed
     */
    final protected function makeCompleted(string $withReason = null): void
    {
        $this->assertNotClosedSaga();

        $this->doChangeState(SagaStatus::completed(), $withReason);
        $this->doClose($withReason);
    }

    /**
     * Change saga status to failed
     *
     * @see SagaStatus::STATUS_FAILED
     *
     * @param string|null $withReason
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\ChangeSagaStateFailed
     */
    final protected function makeFailed(string $withReason = null): void
    {
        $this->assertNotClosedSaga();

        $this->doChangeState(SagaStatus::failed(), $withReason);
        $this->doClose($withReason);
    }

    /**
     * Receive a list of commands that should be fired while saving
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see          SagaProvider::doStore()
     *
     * @return array<int, \Desperado\ServiceBus\Common\Contract\Messages\Command>
     */
    private function firedCommands(): array
    {
        /** @var array<int, \Desperado\ServiceBus\Common\Contract\Messages\Command> $commands */
        $commands = $this->commands;

        $this->clearFiredCommands();

        return $commands;
    }

    /**
     * Receive a list of events that should be published while saving
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see          SagaProvider::doStore()
     *
     * @return array<int, \Desperado\ServiceBus\Common\Contract\Messages\Event>
     */
    private function raisedEvents(): array
    {
        /** @var array<int, \Desperado\ServiceBus\Common\Contract\Messages\Event> $commands */
        $events = $this->events;

        $this->clearRaisedEvents();

        return $events;
    }

    /**
     * Apply event
     *
     * @param Event $event
     *
     * @return void
     */
    private function applyEvent(Event $event): void
    {
        $eventListenerMethodName = self::createListenerName($event);

        /**
         * Call child class method
         *
         * @param Event $event
         *
         * @return void
         */
        $closure = function(Event $event) use ($eventListenerMethodName): void
        {
            if(true === \method_exists($this, $eventListenerMethodName))
            {
                $this->{$eventListenerMethodName}($event);
            }
        };

        $closure->call($this, $event);
    }

    /**
     * Create event listener name
     *
     * @param Event $event
     *
     * @return string
     */
    private static function createListenerName(Event $event): string
    {
        $eventListenerMethodNameParts = \explode('\\', \get_class($event));

        return \sprintf(
            '%s%s',
            self::EVENT_APPLY_PREFIX,
            \end($eventListenerMethodNameParts)
        );
    }

    /**
     * Change saga status to expired
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see SagaStatus::STATUS_EXPIRED
     *
     * @return void
     */
    private function makeExpired(): void
    {
        $this->doChangeState(SagaStatus::expired());
        $this->doClose('expired');
    }

    /**
     * Close saga
     *
     * @param string|null $withReason
     *
     * @return void
     */
    private function doClose(string $withReason = null): void
    {
        $event = SagaClosed::create($this->id, $withReason);

        $this->closedAt = $event->datetime;

        $this->attachEvent($event);
    }

    /**
     * Change saga state
     *
     * @param SagaStatus  $toState
     * @param string|null $withReason
     *
     * @return void
     */
    private function doChangeState(SagaStatus $toState, string $withReason = null): void
    {
        $this->attachEvent(
            SagaStatusChanged::create(
                $this->id,
                $this->status,
                $toState,
                $withReason
            )
        );

        $this->status = $toState;
    }

    /**
     * Clear raised events and fired commands
     *
     * @return void
     */
    private function clear(): void
    {
        $this->clearFiredCommands();
        $this->clearRaisedEvents();
    }

    /**
     * Clear raised events
     *
     * @return void
     */
    private function clearRaisedEvents(): void
    {
        $this->events = [];
    }

    /**
     * Clear fired commands
     *
     * @return void
     */
    private function clearFiredCommands(): void
    {
        $this->commands = [];
    }

    /**
     * @param Event $event
     *
     * @return void
     */
    private function attachEvent(Event $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @param Command $command
     *
     * @return void
     */
    private function attachCommand(Command $command): void
    {
        $this->commands[] = $command;
    }

    /**
     * Checking the possibility of changing the state of the saga
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\ChangeSagaStateFailed
     */
    private function assertNotClosedSaga(): void
    {
        if(false === $this->status->inProgress())
        {
            throw new ChangeSagaStateFailed('Changing the state of the saga is impossible: the saga is complete');
        }
    }

    /**
     * @param SagaId $id
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidSagaIdentifier
     */
    private function assertSagaClassEqualsWithId(SagaId $id): void
    {
        $currentSagaClass = \get_class($this);

        if($currentSagaClass !== $id->sagaClass())
        {
            throw new InvalidSagaIdentifier(
                \sprintf(
                    'The class of the saga in the identifier ("%s") differs from the saga to which it was transmitted ("%s")',
                    $currentSagaClass,
                    $id->sagaClass()
                )
            );
        }
    }

    /**
     * @param \DateTimeImmutable $dateTime
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\InvalidExpireDateInterval
     */
    private function assertExpirationDateIsCorrect(\DateTimeImmutable $dateTime): void
    {
        /** @var \DateTimeImmutable $currentDate */
        $currentDate = datetimeInstantiator('NOW');

        if($currentDate > $dateTime)
        {
            throw new InvalidExpireDateInterval(
                'The expiration date of the saga can not be less than the current date'
            );
        }
    }

    /**
     * Close clone method
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {

    }
}
