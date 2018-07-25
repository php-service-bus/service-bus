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

namespace Desperado\ServiceBus\EventSourcing\EventStreamStore;

/**
 * Aggregate event data
 */
final class StoredAggregateEvent
{
    /**
     * Event ID
     *
     * @var string
     */
    private $eventId;

    /**
     * Playhead position
     *
     * @var int
     */
    private $playheadPosition;

    /**
     * Serialized event data
     *
     * @var string
     */
    private $eventData;

    /**
     * Event class
     *
     * @var string
     */
    private $eventClass;

    /**
     * Occured at datetime
     *
     * @var string
     */
    private $occuredAt;

    /**
     * Recorded at datetime
     *
     * @var string|null
     */
    private $recordedAt;

    /**
     * @param string      $eventId
     * @param int         $playheadPosition
     * @param string      $eventData
     * @param string      $eventClass
     * @param string      $occuredAt
     * @param null|string $recordedAt
     */
    public function __construct(
        string $eventId,
        int $playheadPosition,
        string $eventData,
        string $eventClass,
        string $occuredAt,
        ?string $recordedAt = null
    )
    {
        $this->eventId          = $eventId;
        $this->playheadPosition = $playheadPosition;
        $this->eventData        = $eventData;
        $this->eventClass       = $eventClass;
        $this->occuredAt        = $occuredAt;
        $this->recordedAt       = $recordedAt;
    }

    /**
     * Receive event id
     *
     * @return string
     */
    public function eventId(): string
    {
        return $this->eventId;
    }

    /**
     * Receive playhead position
     *
     * @return int
     */
    public function playheadPosition(): int
    {
        return $this->playheadPosition;
    }

    /**
     * Receive serialized event data
     *
     * @return string
     */
    public function eventData(): string
    {
        return $this->eventData;
    }

    /**
     * Receive event class
     *
     * @return string
     */
    public function eventClass(): string
    {
        return $this->eventClass;
    }

    /**
     * Receive occured at datetime
     *
     * @return string
     */
    public function occuredAt(): string
    {
        return $this->occuredAt;
    }

    /**
     * Receive recorded at datetime
     *
     * @return string|null
     */
    public function recordedAt(): ?string
    {
        return $this->recordedAt;
    }
}
