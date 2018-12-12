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
 * New saga created
 */
final class SagaCreated implements Event
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
     * Date of creation
     *
     * @var \DateTimeImmutable
     */
    public $datetime;

    /**
     * Date of expiration
     *
     * @var \DateTimeImmutable
     */
    public $expirationDate;

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param SagaId             $sagaId
     * @param \DateTimeImmutable $dateTime
     * @param \DateTimeImmutable $expirationDate
     *
     * @return self
     */
    public static function create(SagaId $sagaId, \DateTimeImmutable $dateTime, \DateTimeImmutable $expirationDate): self
    {
        $self = new self();

        $self->id             = (string) $sagaId;
        $self->idClass        = \get_class($sagaId);
        $self->sagaClass      = $sagaId->sagaClass();
        $self->datetime       = $dateTime;
        $self->expirationDate = $expirationDate;

        return $self;
    }
}
