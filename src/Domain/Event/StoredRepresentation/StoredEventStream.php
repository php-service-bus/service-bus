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

namespace Desperado\ConcurrencyFramework\Domain\Event\StoredRepresentation;

/**
 * Stored event stream
 */
class StoredEventStream
{
    /**
     * Stream identity
     *
     * @var string
     */
    private $id;

    /**
     * Stream identity class namespace
     *
     * @var string
     */
    private $class;

    /**
     * Is closed stream
     *
     * @var bool
     */
    private $isClosed;

    /**
     * Stored events
     *
     * @var StoredDomainEvent[]
     */
    private $events;

    /**
     * @param string              $id
     * @param string              $class
     * @param bool                $isClosed
     * @param StoredDomainEvent[] $events
     */
    public function __construct(string $id, string $class, bool $isClosed, array $events)
    {
        $this->id = $id;
        $this->class = $class;
        $this->isClosed = $isClosed;
        $this->events = $events;
    }

    /**
     * Get as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'class'    => $this->class,
            'isClosed' => $this->isClosed,
            'events'   => \array_map(
                function(StoredDomainEvent $storedDomainEvent)
                {
                    return $storedDomainEvent->toArray();
                },
                $this->events
            )
        ];
    }

    /**
     * Get composite stream index
     *
     * @return string
     */
    public function getCompositeIndex(): string
    {
        return \sprintf('%s:%s', $this->class, $this->id);
    }

    /**
     * Get identity
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get identity class namespace
     *
     * @return string
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Get closed stream flag
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return $this->isClosed;
    }

    /**
     * Get stream events
     *
     * @return StoredDomainEvent[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }
}
