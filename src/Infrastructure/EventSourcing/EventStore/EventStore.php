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

namespace Desperado\Framework\Infrastructure\EventSourcing\EventStore;

use Desperado\Framework\Domain\Event\DomainEventStream;
use Desperado\Framework\Domain\EventStore\EventStoreInterface;
use Desperado\Framework\Domain\Identity\IdentityInterface;
use Desperado\Framework\Domain\Serializer\MessageSerializerInterface;
use Desperado\Framework\Infrastructure\EventSourcing\DataTransformer\EventStreamTransformer;
use Desperado\Framework\Infrastructure\EventSourcing\Storage\EventStorageInterface;

/**
 * Event store
 */
class EventStore implements EventStoreInterface
{
    /**
     * Event storage
     *
     * @var EventStorageInterface
     */
    private $storageDriver;

    /**
     * Event stream transformer
     *
     * @var EventStreamTransformer
     */
    private $eventStreamTransformer;

    /**
     * @param EventStorageInterface      $storage
     * @param MessageSerializerInterface $messageSerializer
     */
    public function __construct(EventStorageInterface $storage, MessageSerializerInterface $messageSerializer)
    {
        $this->storageDriver = $storage;
        $this->eventStreamTransformer = new EventStreamTransformer($messageSerializer);
    }

    /**
     * @inheritdoc
     */
    public function load(IdentityInterface $id, callable $onLoaded, callable $onFailed = null): void
    {
        $this->storageDriver->load($id,
            function(array $rows) use ($onLoaded)
            {
                $onLoaded($this->eventStreamTransformer->fromStoredEventStreamData($rows));
            },
            $onFailed
        );
    }

    /**
     * @inheritdoc
     */
    public function loadFromPlayhead(
        IdentityInterface $id,
        int $playhead,
        callable $onLoaded,
        callable $onFailed = null
    ): void
    {
        $this->storageDriver->loadFromPlayhead($id, $playhead,
            function(array $rows) use ($onLoaded)
            {
                $onLoaded($this->eventStreamTransformer->fromStoredEventStreamData($rows));
            },
            $onFailed
        );
    }

    /**
     * @inheritdoc
     */
    public function append(
        IdentityInterface $id,
        DomainEventStream $eventStream,
        callable $onSaved = null,
        callable $onFailed = null
    ): void
    {
        $this->storageDriver->save(
            $this->eventStreamTransformer->toStoredStream($id, $eventStream),
            $onSaved,
            $onFailed
        );
    }
}
