<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Domain\Event;

/**
 * Event stream
 */
final class DomainEventStream implements \Countable, \IteratorAggregate
{
    /**
     * Is stream closed
     *
     * @var bool
     */
    private $isClosed;

    /**
     * Events
     *
     * @var DomainEvent[]
     */
    private $events;

    /**
     * Create stream
     *
     * @param DomainEvent[] $events
     * @param bool          $isClosed
     *
     * @return DomainEventStream
     */
    public static function create(array $events, bool $isClosed = false): self
    {
        return new self($events, $isClosed);
    }

    /**
     * Close stream
     *
     * @return void
     */
    public function closeStream(): void
    {
        $this->isClosed = true;

    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return \count($this->events);
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): \Generator
    {
        yield from $this->events;
    }

    /**
     * Is closed stream
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    /**
     * @param DomainEvent[] $events
     * @param bool          $isClosed
     */
    public function __construct(array $events, bool $isClosed)
    {
        $this->isClosed = $isClosed;
        $this->events = $events;
    }
}
