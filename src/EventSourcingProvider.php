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

namespace Desperado\ServiceBus;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use function Desperado\ServiceBus\Common\createWithoutConstructor;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use Desperado\ServiceBus\EventSourcing\Aggregate;
use Desperado\ServiceBus\EventSourcing\AggregateId;
use Desperado\ServiceBus\EventSourcing\AggregateSnapshot;
use Desperado\ServiceBus\EventSourcing\Contract\AggregateCreated;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\AggregateStore;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\LoadStreamFailed;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\NonUniqueStreamId;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\SaveStreamFailed;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\StoredAggregateEventStream;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Transformer\AggregateEventSerializer;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Transformer\AggregateEventStreamDataTransformer;
use Desperado\ServiceBus\EventSourcing\EventStreamStore\Transformer\DefaultEventSerializer;
use Desperado\ServiceBus\EventSourcing\Snapshotter;

/**
 *
 */
final class EventSourcingProvider
{
    public const REVERT_MODE_SOFT_DELETE = 1;
    public const REVERT_MODE_DELETE      = 2;

    /**
     * Event streams store
     *
     * @var AggregateStore
     */
    private $storage;

    /**
     * Stream transform handler
     *
     * @var AggregateEventStreamDataTransformer
     */
    private $streamDataTransformer;

    /**
     * Snapshots provider
     *
     * @var Snapshotter
     */
    private $snapshotter;

    /**
     * @param AggregateStore                $storage
     * @param Snapshotter                   $snapshotter
     * @param AggregateEventSerializer|null $serializer
     */
    public function __construct(
        AggregateStore $storage,
        Snapshotter $snapshotter,
        AggregateEventSerializer $serializer = null
    )
    {
        $this->storage     = $storage;
        $this->snapshotter = $snapshotter;

        $this->streamDataTransformer = new AggregateEventStreamDataTransformer(
            $serializer ?? new DefaultEventSerializer()
        );
    }

    /**
     * Create a new aggregate
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param AggregateId            $id
     * @param MessageDeliveryContext $context
     *
     * @return Promise<\Desperado\ServiceBus\EventSourcing\Aggregate>
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\NonUniqueStreamId
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\SaveStreamFailed
     */
    public function create(AggregateId $id, MessageDeliveryContext $context): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(AggregateId $id, MessageDeliveryContext $context): \Generator
            {
                $aggregateClass = $id->aggregateClass();

                /** @var Aggregate $aggregate */
                $aggregate = new $aggregateClass($id);

                yield $this->save($aggregate, $context);

                return $aggregate;
            },
            $id, $context
        );
    }

    /**
     * Load aggregate
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @param AggregateId $id
     *
     * @return Promise<\Desperado\ServiceBus\EventSourcing\Aggregate|null>
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\LoadStreamFailed
     */
    public function load(AggregateId $id): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(AggregateId $id): \Generator
            {
                try
                {
                    $aggregate         = null;
                    $fromStreamVersion = Aggregate::START_PLAYHEAD_INDEX;

                    /** @var AggregateSnapshot|null $loadedSnapshot */
                    $loadedSnapshot = yield $this->snapshotter->load($id);

                    if(null !== $loadedSnapshot)
                    {
                        $aggregate         = $loadedSnapshot->aggregate;
                        $fromStreamVersion = $aggregate->version() + 1;
                    }

                    /** @var StoredAggregateEventStream|null $storedEventStream */
                    $storedEventStream = yield $this->storage->loadStream($id, $fromStreamVersion);

                    $aggregate = $this->restoreStream($aggregate, $storedEventStream);

                    unset($storedEventStream, $loadedSnapshot, $fromStreamVersion);

                    return $aggregate;
                }
                catch(\Throwable $throwable)
                {
                    throw new LoadStreamFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            },
            $id
        );
    }

    /**
     * Save new aggregate
     *
     * @param Aggregate              $aggregate
     * @param MessageDeliveryContext $context
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\NonUniqueStreamId
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\SaveStreamFailed
     */
    public function save(Aggregate $aggregate, MessageDeliveryContext $context): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(Aggregate $aggregate, MessageDeliveryContext $context): \Generator
            {
                try
                {
                    /** @var \Desperado\ServiceBus\EventSourcing\EventStream\AggregateEventStream $eventStream */
                    $eventStream    = invokeReflectionMethod($aggregate, 'makeStream');
                    $receivedEvents = $eventStream->originEvents;

                    $storedEventStream = $this->streamDataTransformer->streamToStoredRepresentation($eventStream);

                    true === self::isNewEventStream($receivedEvents)
                        ? yield $this->storage->saveStream($storedEventStream)
                        : yield $this->storage->appendStream($storedEventStream);

                    /** @var AggregateSnapshot|null $loadedSnapshot */
                    $loadedSnapshot = yield $this->snapshotter->load($aggregate->id());

                    if(true === $this->snapshotter->snapshotMustBeCreated($aggregate, $loadedSnapshot))
                    {
                        yield $this->snapshotter->store(new AggregateSnapshot($aggregate, $aggregate->version()));
                    }

                    yield from self::publishEvents($receivedEvents, $context);

                    unset($eventStream, $receivedEvents, $loadedSnapshot);
                }
                catch(NonUniqueStreamId $exception)
                {
                    throw $exception;
                }
                catch(\Throwable $throwable)
                {
                    throw new SaveStreamFailed($throwable->getMessage(), (int) $throwable->getCode(), $throwable);
                }
            },
            $aggregate,
            $context
        );
    }

    /**
     * Revert aggregate to specified version
     *
     * Mode options:
     *   - 1 (self::REVERT_MODE_SOFT_DELETE): Mark tail events as deleted (soft deletion). There may be version conflicts in some situations
     *   - 2 (self::REVERT_MODE_DELETE): Removes tail events from the database (the best option)
     *
     * @param Aggregate $aggregate
     * @param int       $toVersion
     * @param int       $mode
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress MixedTypeCoercion Incorrect resolving the value of the promise
     *
     * @return Promise<\Desperado\ServiceBus\EventSourcing\Aggregate> Returns a new aggregate object
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\EventStreamDoesNotExist
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\EventStreamIntegrityCheckFailed
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\StreamRevertFailed
     */
    public function revert(Aggregate $aggregate, int $toVersion, int $mode = self::REVERT_MODE_SOFT_DELETE): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(Aggregate $aggregate, int $toVersion, int $mode): \Generator
            {
                yield $this->storage->revertStream(
                    $aggregate->id(),
                    $toVersion,
                    self::REVERT_MODE_DELETE === $mode
                );

                /** @var StoredAggregateEventStream|null $storedEventStream */
                $storedEventStream = yield $this->storage->loadStream($aggregate->id());

                /** @var Aggregate $aggregate */
                $aggregate = $this->restoreStream(null, $storedEventStream);

                yield $this->snapshotter->store(new AggregateSnapshot($aggregate, $aggregate->version()));

                return $aggregate;
            },
            $aggregate, $toVersion, $mode
        );
    }

    /**
     * Send events to transport
     *
     * @param array<int, \Desperado\ServiceBus\Common\Contract\Messages\Event> $events
     * @param MessageDeliveryContext                                           $context
     *
     * @return \Generator It does not return any result
     */
    private static function publishEvents(array $events, MessageDeliveryContext $context): \Generator
    {
        /** @var Event $event */
        foreach($events as $event)
        {
            yield $context->delivery($event);
        }
    }

    /**
     * Restore the aggregate from the event stream/Add missing events to the aggregate from the snapshot
     *
     * @param Aggregate                       $aggregate
     * @param StoredAggregateEventStream|null $storedEventStream
     *
     * @return Aggregate|null
     *
     * @throws \ReflectionException
     */
    private function restoreStream(?Aggregate $aggregate, ?StoredAggregateEventStream $storedEventStream): ?Aggregate
    {
        if(null !== $storedEventStream)
        {
            $eventStream = $this->streamDataTransformer->streamToDomainRepresentation($storedEventStream);

            if(null === $aggregate)
            {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                /** @var Aggregate $aggregate */
                $aggregate = createWithoutConstructor($storedEventStream->aggregateClass);
            }

            invokeReflectionMethod($aggregate, 'appendStream', $eventStream);
        }

        return $aggregate;
    }

    /**
     * If there is an aggregate creation event in the event stream, then it was not stored in the database (usually the
     * event is the first)
     *
     * @param array<int, \Desperado\ServiceBus\Common\Contract\Messages\Event> $events
     *
     * @return bool
     */
    private static function isNewEventStream(array $events): bool
    {
        /** @var Event $event */
        foreach($events as $event)
        {
            if($event instanceof AggregateCreated)
            {
                return true;
            }
        }

        return false;
    }
}
