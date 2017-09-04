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

namespace Desperado\Framework\Infrastructure\EventSourcing\Storage\InMemory;

use Desperado\Framework\Domain\Event\StoredRepresentation\StoredEventStream;
use Desperado\Framework\Domain\Identity\IdentityInterface;
use Desperado\Framework\Infrastructure\EventSourcing\Storage\DuplicatePlayheadException;
use Desperado\Framework\Infrastructure\EventSourcing\Storage\EventStorageInterface;

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
    public function load(IdentityInterface $id, callable $onLoaded, callable $onFailed = null): void
    {
        $streamId = $id->toCompositeIndex();

        $result = false !== \array_key_exists($streamId, $this->storage)
            ? $this->storage[$streamId]
            : [];

        $onLoaded($result);
    }

    /**
     * @inheritdoc
     */
    public function loadFromPlayhead(
        IdentityInterface $id,
        int $playheadPosition,
        callable $onLoaded,
        callable $onFailed = null
    ): void
    {
        $result = [];
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

            $result = [
                'isClosed' => $this->storage[$streamId]['isClosed'],
                'events'   => $events
            ];
        }

        $onLoaded($result);
    }

    /**
     * @inheritdoc
     */
    public function save(
        StoredEventStream $storedEventStream,
        callable $onSaved = null,
        callable $onFailed = null
    ): void
    {
        try
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

            if(null !== $onSaved)
            {
                $onSaved();
            }
        }
        catch(\Throwable $throwable)
        {
            if(null !== $onFailed)
            {
                $onFailed($throwable);
            }
        }
    }
}
