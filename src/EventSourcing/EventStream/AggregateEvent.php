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

namespace Desperado\ServiceBus\EventSourcing\EventStream;

use Desperado\ServiceBus\Common\Contract\Messages\Event;

/**
 * Applied to aggregate event
 */
final class AggregateEvent
{
    /**
     * Event id
     *
     * @var string
     */
    public $id;

    /**
     * Playhead position
     *
     * @var int
     */
    public $playhead;

    /**
     * Received event
     *
     * @var Event
     */
    public $event;

    /**
     * Occurred datetime
     *
     * @var \DateTimeImmutable
     */
    public $occuredAt;

    /**
     * Recorded datetime
     *
     * @var \DateTimeImmutable|null
     */
    public $recordedAt;

    /**
     * @param string                  $id
     * @param Event                   $event
     * @param int                     $playhead
     * @param \DateTimeImmutable      $occuredAt
     * @param \DateTimeImmutable|null $recordedAt
     */
    public function __construct(
        string $id,
        Event $event,
        int $playhead,
        \DateTimeImmutable $occuredAt,
        ?\DateTimeImmutable $recordedAt = null
    )
    {
        $this->id         = $id;
        $this->event      = $event;
        $this->playhead   = $playhead;
        $this->occuredAt  = $occuredAt;
        $this->recordedAt = $recordedAt;
    }
}
