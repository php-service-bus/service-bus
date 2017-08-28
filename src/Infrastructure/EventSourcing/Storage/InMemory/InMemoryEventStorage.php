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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\InMemory;

use Desperado\ConcurrencyFramework\Domain\Event\StoredRepresentation\StoredEventStream;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\DuplicatePlayheadException;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\EventStorageInterface;

/**
 * InMemory storage backed
 */
class InMemoryEventStorage implements EventStorageInterface
{
    /**
     * Local storage
     *
     * @var array
     */
    private $storage = [];

    /**
     * @inheritdoc
     */
    public function load(IdentityInterface $id): array
    {
        $streamId = $id->toCompositeIndex();

        return false !== \array_key_exists($streamId, $this->storage)
            ? $this->storage[$streamId]
            : [];
    }

    /**
     * @inheritdoc
     */
    public function loadFromPlayhead(IdentityInterface $id, int $playheadPosition): array
    {
        $streamId = $id->toCompositeIndex();

        if(false !== \array_key_exists($streamId, $this->storage))
        {
            $events = [];

            \array_map(
                function(array $eachEvent) use (&$events, $playheadPosition)
                {
                    if($playheadPosition <= $eachEvent['playhead'])
                    {
                        $events[$eachEvent['playhead']] = $eachEvent;
                    }
                }, $this->storage[$streamId]['events']
            );

            return [
                'isClosed' => $this->storage[$streamId]['isClosed'],
                'events'   => $events
            ];
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function save(StoredEventStream $storedEventStream): void
    {
        $streamId = \sprintf('%s:%s', $storedEventStream->getClass(), $storedEventStream->getId());

        if(false === isset($this->storage[$streamId]))
        {
            $this->storage[$streamId] = [];
        }

        $this->storage[$streamId]['isClosed'] = $storedEventStream->isClosed();

        foreach($storedEventStream->getEvents() as $storedEvent)
        {
            if(true === isset($this->storage[$streamId]['events'][$storedEvent->getPlayhead()]))
            {
                throw new DuplicatePlayheadException(
                    \sprintf(
                        'Duplicate playhead ("%s") for stream with ID "%s"',
                        $storedEvent->getPlayhead(), $storedEventStream->getId()
                    )
                );
            }

            $this->storage[$streamId]['events'][$storedEvent->getPlayhead()] = $storedEvent->toArray();
        }
    }
}
