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

namespace Desperado\ServiceBus\EventSourcing\Contract\EventSourcing;

use Desperado\ServiceBus\Common\Contract\Messages\Event;

/**
 * New aggregate created
 */
final class AggregateCreated implements Event
{
    /**
     * Aggregate identifier
     *
     * @var string
     */
    private $id;

    /**
     * Aggregate identifier class
     *
     * @var string
     */
    private $idClass;

    /**
     * Aggregate class
     *
     * @var string
     */
    private $aggregateClass;

    /**
     * Operation datetime
     *
     * @var \DateTimeImmutable
     */
    private $datetime;

    /**
     * @noinspection PhpDocMissingThrowsInspection
     *
     * @param string $id
     * @param string $idClass
     * @param string $aggregateClass
     *
     * @return self
     */
    public static function create(string $id, string $idClass, string $aggregateClass): self
    {
        $self = new self();

        $self->id             = $id;
        $self->idClass        = $idClass;
        $self->aggregateClass = $aggregateClass;
        /** @noinspection PhpUnhandledExceptionInspection */
        $self->datetime = new \DateTimeImmutable('NOW');

        return $self;
    }

    /**
     * Receive aggregate identifier
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Receive aggregate identifier class
     *
     * @return string
     */
    public function idClass(): string
    {
        return $this->idClass;
    }

    /**
     * Receive aggregate class
     *
     * @return string
     */
    public function aggregateClass(): string
    {
        return $this->aggregateClass;
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
