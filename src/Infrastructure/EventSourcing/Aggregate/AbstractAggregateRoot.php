<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Aggregate;

use Desperado\ConcurrencyFramework\Domain\DateTime;
use Desperado\ConcurrencyFramework\Domain\EventSourced\AggregateRootInterface;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\AbstractEventSourced;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Contract\EventSourcedEntryCreatedEvent;

/**
 * Aggregate root
 */
abstract class AbstractAggregateRoot extends AbstractEventSourced implements AggregateRootInterface
{
    /**
     * Create new aggregate
     *
     * @param IdentityInterface $identity
     *
     * @return $this
     */
    final protected static function new(IdentityInterface $identity): self
    {
        $self = new static();

        $createdEvent = new EventSourcedEntryCreatedEvent();
        $createdEvent->id = $identity->toString();
        $createdEvent->type = \get_class($identity);
        $createdEvent->createdAt = DateTime::nowToString();

        $self->raiseEvent($createdEvent);

        return $self;
    }
}
