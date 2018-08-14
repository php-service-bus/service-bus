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
use Amp\Success;
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
use Desperado\ServiceBus\EventSourcingSnapshots\Snapshotter;

/**
 *
 */
final class EventSourcingProvider
{
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
     * Load aggregate
     *
     * @param AggregateId $id
     *
     * @return Promise<\Desperado\EventSourcing\Aggregate\AggregateRoot|null>
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\LoadStreamFailed
     */
    public function load(AggregateId $id): Promise
    {
        $storage     = $this->storage;
        $transformer = $this->streamDataTransformer;
        $snapshotter = $this->snapshotter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(AggregateId $id) use ($storage, $transformer, $snapshotter): \Generator
            {
                try
                {
                    $aggregate = null;

                    /** @var AggregateSnapshot|null $loadedSnapshot */
                    $loadedSnapshot = yield $snapshotter->load($id);

                    if(null !== $loadedSnapshot)
                    {
                        $aggregate = $loadedSnapshot->aggregate();
                    }

                    $fromStreamVersion = null !== $aggregate
                        ? $aggregate->version() + 1
                        : Aggregate::START_PLAYHEAD_INDEX;


                    /** @var StoredAggregateEventStream|null $storedEventStream */
                    $storedEventStream = yield  $storage->loadStream($id, $fromStreamVersion);

                    $aggregate = self::restoreStream($aggregate, $storedEventStream, $transformer);

                    return yield new Success($aggregate);
                }
                catch(\Throwable $throwable)
                {
                    throw new LoadStreamFailed(
                        $throwable->getMessage(),
                        $throwable->getCode(),
                        $throwable
                    );
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
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<void>
     *
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\NonUniqueStreamId
     * @throws \Desperado\ServiceBus\EventSourcing\EventStreamStore\Exceptions\SaveStreamFailed
     */
    public function save(Aggregate $aggregate, MessageDeliveryContext $context): Promise
    {
        $storage     = $this->storage;
        $transformer = $this->streamDataTransformer;
        $snapshotter = $this->snapshotter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Aggregate $aggregate, MessageDeliveryContext $context) use ($storage, $transformer, $snapshotter): \Generator
            {
                try
                {
                    $eventStream    = invokeReflectionMethod($aggregate, 'makeStream');
                    $receivedEvents = $eventStream->originEvents();

                    $afterSave = static function() use ($context, $receivedEvents): \Generator
                    {
                        yield $context->delivery(... $receivedEvents);
                    };

                    $storedEventStream = $transformer->streamToStoredRepresentation($eventStream);

                    true === self::isNewEventStream($receivedEvents)
                        ? yield $storage->saveStream($storedEventStream, $afterSave)
                        : yield $storage->appendStream($storedEventStream, $afterSave);

                    /** @var AggregateSnapshot|null $loadedSnapshot */
                    $loadedSnapshot = yield $snapshotter->load($aggregate->id());

                    if(true === $snapshotter->snapshotMustBeCreated($aggregate, $loadedSnapshot))
                    {
                        yield $snapshotter->store(new AggregateSnapshot($aggregate, $aggregate->version()));
                    }

                    return yield new Success();
                }
                catch(NonUniqueStreamId $exception)
                {
                    throw $exception;
                }
                catch(\Throwable $throwable)
                {
                    throw new SaveStreamFailed(
                        $throwable->getMessage(),
                        $throwable->getCode(),
                        $throwable
                    );
                }
            },
            $aggregate,
            $context
        );
    }

    /**
     * Restore the aggregate from the event stream/Add missing events to the aggregate from the snapshot
     *
     * @param Aggregate|null                      $aggregate
     * @param StoredAggregateEventStream|null     $storedEventStream
     * @param AggregateEventStreamDataTransformer $transformer
     *
     * @return Aggregate|null
     *
     * @throws \ReflectionException
     */
    private static function restoreStream(
        ?Aggregate $aggregate,
        ?StoredAggregateEventStream $storedEventStream,
        AggregateEventStreamDataTransformer $transformer
    ): ?Aggregate
    {
        if(null !== $storedEventStream)
        {
            $eventStream = $transformer->streamToDomainRepresentation($storedEventStream);

            if(null === $aggregate)
            {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                /** @var Aggregate $aggregate */
                $aggregate = createWithoutConstructor($storedEventStream->aggregateClass());
            }

            invokeReflectionMethod($aggregate, 'appendStream', $eventStream);
        }

        return $aggregate;
    }

    /**
     * If there is an aggregate creation event in the event stream, then it was not stored in the database (usually the
     * event is the first)
     *
     * @param array $events
     *
     * @return bool
     */
    private static function isNewEventStream(array $events): bool
    {
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
