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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\EventStore;

use Desperado\ConcurrencyFramework\Domain\Event\DomainEventStream;
use Desperado\ConcurrencyFramework\Domain\EventStore\EventStoreInterface;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\DataTransformer\EventStreamTransformer;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\EventStorageInterface;

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
    public function load(IdentityInterface $id): ?DomainEventStream
    {
        return $this->eventStreamTransformer->fromStoredEventStreamData(
            $this->storageDriver->load($id)
        );
    }

    /**
     * @inheritdoc
     */
    public function loadFromPlayhead(IdentityInterface $id, int $playhead): ?DomainEventStream
    {
        return $this->eventStreamTransformer->fromStoredEventStreamData(
            $this->storageDriver->loadFromPlayhead($id, $playhead)
        );
    }

    /**
     * @inheritdoc
     */
    public function append(IdentityInterface $id, DomainEventStream $eventStream): void
    {
        $this->storageDriver->save(
            $this->eventStreamTransformer->toStoredStream($id, $eventStream)
        );
    }
}
