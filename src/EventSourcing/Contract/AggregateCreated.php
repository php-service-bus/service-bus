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

namespace Desperado\ServiceBus\EventSourcing\Contract;

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
    public $id;

    /**
     * Aggregate identifier class
     *
     * @var string
     */
    public $idClass;

    /**
     * Aggregate class
     *
     * @var string
     */
    public $aggregateClass;

    /**
     * Operation datetime
     *
     * @var \DateTimeImmutable
     */
    public $datetime;

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
}
