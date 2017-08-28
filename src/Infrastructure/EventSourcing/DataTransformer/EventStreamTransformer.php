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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\DataTransformer;

use Desperado\ConcurrencyFramework\Domain\DateTime;
use Desperado\ConcurrencyFramework\Domain\Event\DomainEvent;
use Desperado\ConcurrencyFramework\Domain\Event\DomainEventStream;
use Desperado\ConcurrencyFramework\Domain\Event\StoredRepresentation\StoredDomainEvent;
use Desperado\ConcurrencyFramework\Domain\Event\StoredRepresentation\StoredEventStream;
use Desperado\ConcurrencyFramework\Domain\Identity\IdentityInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Domain\Serializer\MessageSerializerInterface;

/**
 * Event stream transformer
 */
class EventStreamTransformer
{
    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * @param MessageSerializerInterface $messageSerializer
     */
    public function __construct(MessageSerializerInterface $messageSerializer)
    {
        $this->messageSerializer = $messageSerializer;
    }

    /**
     * Domain event stream to stored representation
     *
     * @param IdentityInterface $streamId
     * @param DomainEventStream $eventStream
     *
     * @return StoredEventStream
     */
    public function toStoredStream(IdentityInterface $streamId, DomainEventStream $eventStream): StoredEventStream
    {
        $eventsData = [];

        foreach($eventStream as $domainEvent)
        {
            /** @var DomainEvent $domainEvent */

            $storedDomainEvent = new StoredDomainEvent(
                $domainEvent->getId(),
                $domainEvent->getPlayhead(),
                $this->messageSerializer->serialize($domainEvent->getReceivedEvent()),
                $domainEvent->getOccurredAt()->toString(),
                DateTime::nowToString()
            );

            $eventsData[$domainEvent->getPlayhead()] = $storedDomainEvent;
        }

        $storedEventStream = new StoredEventStream(
            $streamId->toString(),
            \get_class($streamId),
            $eventStream->isClosed(),
            $eventsData
        );

        return $storedEventStream;
    }

    /**
     * Stored domain event stream representation to object
     *
     * @param array $storedEventStreamData
     *
     * @return DomainEventStream|null
     */
    public function fromStoredEventStreamData(array $storedEventStreamData): ?DomainEventStream
    {
        if(0 !== \count($storedEventStreamData))
        {
            return DomainEventStream::create(
                \array_map(
                    function(array $eachEvent)
                    {
                        /** @var EventInterface $receivedEvent */
                        $receivedEvent = $this->messageSerializer
                            ->unserialize($eachEvent['receivedEvent'])
                            ->getMessage();

                        return DomainEvent::restore(
                            $eachEvent['id'],
                            $receivedEvent,
                            $eachEvent['playhead'],
                            DateTime::fromString($eachEvent['occurredAt']),
                            DateTime::fromString($eachEvent['recordedAt'])
                        );
                    },
                    $storedEventStreamData['events']
                ), $storedEventStreamData['isClosed']
            );
        }

        return null;
    }
}
