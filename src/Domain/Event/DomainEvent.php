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

use Desperado\ConcurrencyFramework\Domain\DateTime;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Domain\Uuid;

/**
 * Domain event
 */
final class DomainEvent
{
    /**
     * Event ID
     *
     * @var string
     */
    private $id;

    /**
     * Playhead position
     *
     * @var int
     */
    private $playhead;

    /**
     * Event message
     *
     * @var EventInterface
     */
    private $receivedEvent;

    /**
     * Occurred datetime
     *
     * @var DateTime
     */
    private $occurredAt;

    /**
     * Recorded datetime
     *
     * @var DateTime|null
     */
    private $recordedAt;

    /**
     * Create new domain event
     *
     * @param EventInterface $receivedEvent
     * @param int            $playhead
     *
     * @return DomainEvent
     */
    public static function new(EventInterface $receivedEvent, int $playhead): self
    {
        return new self(
            Uuid::new(),
            $playhead,
            $receivedEvent,
            DateTime::now()
        );
    }

    /**
     * Restore domain event
     *
     * @param string         $id
     * @param EventInterface $receivedEvent
     * @param int            $playhead
     * @param DateTime       $occurredAt
     * @param DateTime|null  $recordedAt
     *
     * @return DomainEvent
     */
    public static function restore(
        string $id,
        EventInterface $receivedEvent,
        int $playhead,
        DateTime $occurredAt,
        DateTime $recordedAt = null
    ): self
    {
        return new self(
            $id,
            $playhead,
            $receivedEvent,
            $occurredAt,
            $recordedAt
        );
    }

    /**
     * Event ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get playhead position
     *
     * @return int
     */
    public function getPlayhead(): int
    {
        return $this->playhead;
    }

    /**
     * Get event
     *
     * @return EventInterface
     */
    public function getReceivedEvent(): EventInterface
    {
        return $this->receivedEvent;
    }

    /**
     * Get Occurred datetime
     *
     * @return DateTime
     */
    public function getOccurredAt(): DateTime
    {
        return $this->occurredAt;
    }

    /**
     * Recorded datetime
     *
     * @return DateTime|null
     */
    public function getRecordedAt(): ?DateTime
    {
        return $this->recordedAt;
    }

    /**
     * @param string         $id
     * @param int            $playhead
     * @param EventInterface $receivedEvent
     * @param DateTime       $occurredAt
     * @param DateTime|null  $recordedAt
     */
    private function __construct(
        string $id,
        int $playhead,
        EventInterface $receivedEvent,
        DateTime $occurredAt,
        DateTime $recordedAt = null
    )
    {
        $this->id = $id;
        $this->playhead = $playhead;
        $this->receivedEvent = $receivedEvent;
        $this->occurredAt = $occurredAt;
        $this->recordedAt = $recordedAt;
    }
}
