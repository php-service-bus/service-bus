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

namespace Desperado\ServiceBus\Sagas\Contract;

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
    public $id;

    /**
     * Saga identifier class
     *
     * @var string
     */
    public $idClass;

    /**
     * Saga class
     *
     * @var string
     */
    public $sagaClass;

    /**
     * Previous saga status
     *
     * @var string
     */
    public $previousStatus;

    /**
     * Previous saga status
     *
     * @var string
     */
    public $newStatus;

    /**
     * Reason for changing the status of the saga
     *
     * @var string|null
     */
    public $withReason;

    /**
     * Operation datetime
     *
     * @var \DateTimeImmutable
     */
    public $datetime;

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
}
