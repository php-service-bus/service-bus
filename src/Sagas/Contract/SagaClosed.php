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

/**
 * The saga was completed
 */
final class SagaClosed implements Event
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
     * Reason for closing the saga
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
     * @param string|null $withReason
     *
     * @return self
     */
    public static function create(SagaId $sagaId, ?string $withReason = null): self
    {
        $self = new self();

        $self->id        = (string) $sagaId;
        $self->idClass   = \get_class($sagaId);
        $self->sagaClass = $sagaId->sagaClass();
        /** @noinspection PhpUnhandledExceptionInspection */
        $self->datetime   = new \DateTimeImmutable('NOW');
        $self->withReason = $withReason;

        return $self;
    }
}
