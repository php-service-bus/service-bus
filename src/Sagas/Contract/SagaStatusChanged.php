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

namespace Desperado\ServiceBus\Sagas\Contract\Sagas;

use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Sagas\SagaId;
use Desperado\ServiceBus\Sagas\SagaStatus;

/**
 * The status of the saga was changed
 */
final class SagaStatusChanged implements Event
{
    /**
     * Saga identifier
     *
     * @var string
     */
    private $id;

    /**
     * Saga identifier class
     *
     * @var string
     */
    private $idClass;

    /**
     * Saga class
     *
     * @var string
     */
    private $sagaClass;

    /**
     * Previous saga status
     *
     * @var string
     */
    private $previousStatus;

    /**
     * Previous saga status
     *
     * @var string
     */
    private $newStatus;

    /**
     * Reason for changing the status of the saga
     *
     * @var string|null
     */
    private $withReason;

    /**
     * Operation datetime
     *
     * @var \DateTimeImmutable
     */
    private $datetime;

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param SagaId      $sagaId
     * @param SagaStatus  $currentStatus
     * @param SagaStatus  $newStatus
     * @param null|string $withReason
     *
     * @return self
     */
    public static function create(
        SagaId $sagaId,
        SagaStatus $currentStatus,
        SagaStatus $newStatus,
        ?string $withReason = null
    ): self
    {
        $self = new self();

        $self->id             = (string) $sagaId;
        $self->idClass        = \get_class($sagaId);
        $self->sagaClass      = $sagaId->sagaClass();
        $self->previousStatus = (string) $currentStatus;
        $self->newStatus      = (string) $newStatus;
        $self->withReason     = $withReason;
        /** @noinspection PhpUnhandledExceptionInspection */
        $self->datetime = new \DateTimeImmutable('NOW');

        return $self;
    }

    /**
     * Receive saga identifier
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Receive saga identifier class
     *
     * @return string
     */
    public function idClass(): string
    {
        return $this->idClass;
    }

    /**
     * Receive saga class
     *
     * @return string
     */
    public function sagaClass(): string
    {
        return $this->sagaClass;
    }

    /**
     * Receive old saga status id
     *
     * @return string
     */
    public function previousStatus(): string
    {
        return $this->previousStatus;
    }

    /**
     * Receive new saga status id
     *
     * @return string
     */
    public function newStatus(): string
    {
        return $this->newStatus;
    }

    /**
     * Receive the reason for changing the status of the saga
     *
     * @return string|null
     */
    public function withReason(): ?string
    {
        return $this->withReason;
    }

    /**
     * Receive operation datetime
     *
     * @return \DateTimeImmutable
     */
    public function datetime(): \DateTimeImmutable
    {
        return $this->datetime;
    }
}
