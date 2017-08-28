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
 * Stored domain event DTO
 */
class StoredDomainEvent
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
     * Serialized event representation
     *
     * @var string
     */
    private $receivedEvent;

    /**
     * Occurred datetime
     *
     * @var string
     */
    private $occurredAt;

    /**
     * Recorded datetime
     *
     * @var string
     */
    private $recordedAt;

    /**
     * @param string $id
     * @param int    $playhead
     * @param string $receivedEvent
     * @param string $occurredAt
     * @param string $recordedAt
     */
    public function __construct(string $id, int $playhead, string $receivedEvent, string $occurredAt, string $recordedAt)
    {
        $this->id = $id;
        $this->playhead = $playhead;
        $this->receivedEvent = $receivedEvent;
        $this->occurredAt = $occurredAt;
        $this->recordedAt = $recordedAt;
    }

    /**
     * Get as array
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'playhead'      => $this->playhead,
            'receivedEvent' => $this->receivedEvent,
            'occurredAt'    => $this->occurredAt,
            'recordedAt'    => $this->occurredAt
        ];
    }

    /**
     * Get ID
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
     * Get serialized event representation
     *
     * @return string
     */
    public function getReceivedEvent(): string
    {
        return $this->receivedEvent;
    }

    /**
     * Get occurred at datetime
     *
     * @return string
     */
    public function getOccurredAt(): string
    {
        return $this->occurredAt;
    }

    /**
     * Get recorded at datetime
     *
     * @return string
     */
    public function getRecordedAt(): string
    {
        return $this->recordedAt;
    }
}
