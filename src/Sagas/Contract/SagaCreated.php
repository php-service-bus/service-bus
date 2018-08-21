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
     * Date of creation
     *
     * @var \DateTimeImmutable
     */
    private $datetime;

    /**
     * Date of expiration
     *
     * @var \DateTimeImmutable
     */
    private $expirationDate;

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
     * Receive date of creation
     *
     * @return \DateTimeImmutable
     */
    public function datetime(): \DateTimeImmutable
    {
        return $this->datetime;
    }

    /**
     * Receive date of expiration
     *
     * @return \DateTimeImmutable
     */
    public function expirationDate(): \DateTimeImmutable
    {
        return $this->expirationDate;
    }
}
