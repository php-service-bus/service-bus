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
    public $eventId;

    /**
     * Playhead position
     *
     * @var int
     */
    public $playheadPosition;

    /**
     * Serialized event data
     *
     * @var string
     */
    public $eventData;

    /**
     * Event class
     *
     * @var string
     */
    public $eventClass;

    /**
     * Occured at datetime
     *
     * @var string
     */
    public $occuredAt;

    /**
     * Recorded at datetime
     *
     * @var string|null
     */
    public $recordedAt;

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
}
