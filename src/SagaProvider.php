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
use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use function Desperado\ServiceBus\Common\invokeReflectionMethod;
use function Desperado\ServiceBus\Common\readReflectionPropertyValue;
use Desperado\ServiceBus\Sagas\Exceptions\DuplicateSagaId;
use Desperado\ServiceBus\Sagas\Exceptions\LoadSagaFailed;
use Desperado\ServiceBus\Sagas\Exceptions\SaveSagaFailed;
use Desperado\ServiceBus\Sagas\Exceptions\StartSagaFailed;
use Desperado\ServiceBus\Sagas\Saga;
use Desperado\ServiceBus\Sagas\SagaId;
use Desperado\ServiceBus\Sagas\SagaStore\SagasStore;
use Desperado\ServiceBus\Sagas\SagaStore\StoredSaga;
use Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationCheckFailed;

/**
 * Saga provider
 *
 * @todo: clear old contexts
 */
final class SagaProvider
{
    /**
     * Sagas store
     *
     * @var SagasStore
     */
    private $store;

    /**
     * Contexts in which sagas are performed
     *
     * @var array<string, \Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext>
     */
    private $sagasContexts = [];

    /**
     * @param SagasStore $store
     */
    public function __construct(SagasStore $store)
    {
        $this->store = $store;
    }

    /**
     * Start new saga
     *
     * @param SagaId                 $id
     * @param Command                $command
     * @param MessageDeliveryContext $context
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<\Desperado\ServiceBus\Sagas\Saga>
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\StartSagaFailed
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\DuplicateSagaId
     */
    public function start(SagaId $id, Command $command, MessageDeliveryContext $context): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(SagaId $id, Command $command, MessageDeliveryContext $context): \Generator
            {
                try
                {
                    $sagaClass = $id->sagaClass();

                    /** @var Saga $saga */
                    $saga = new $sagaClass($id);
                    $saga->start($command);

                    /** Store context */
                    $this->sagasContexts[\spl_object_hash($saga)] = $context;

                    yield self::doStore($this->store, $saga, $context, true);

                    return yield new Success($saga);
                }
                catch(UniqueConstraintViolationCheckFailed $exception)
                {
                    throw new DuplicateSagaId($id);
                }
                catch(\Throwable $throwable)
                {
                    throw new StartSagaFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
            },
            $id, $command, $context
        );
    }

    /**
     * Load saga
     *
     * @param SagaId                 $id
     * @param MessageDeliveryContext $context
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<\Desperado\ServiceBus\Sagas\Saga|null>
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\LoadSagaFailed
     */
    public function obtain(SagaId $id, MessageDeliveryContext $context): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(SagaId $id, MessageDeliveryContext $context): \Generator
            {
                try
                {
                    /** @var Saga|null $saga */
                    $saga = yield self::doLoad($this->store, $id);

                    if(null !== $saga)
                    {
                        $sagaHash = \spl_object_hash($saga);

                        if(false === isset($this->sagasContexts[$sagaHash]))
                        {
                            $this->sagasContexts[$sagaHash] = $context;
                        }
                    }

                    return yield new Success($saga);
                }
                catch(\Throwable $throwable)
                {
                    throw new LoadSagaFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
            },
            $id, $context
        );
    }

    /**
     * Save saga
     *
     * @param Saga $saga
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Sagas\Exceptions\SaveSagaFailed
     */
    public function save(Saga $saga): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            function(Saga $saga): \Generator
            {
                try
                {
                    /** @var Saga|null $existsSaga */
                    $existsSaga = yield self::doLoad($this->store, $saga->id());

                    if(null !== $existsSaga)
                    {
                        yield self::doStore(
                            $this->store,
                            $saga,
                            $this->sagasContexts[\spl_object_hash($saga)],
                            false
                        );

                        return yield new Success();
                    }

                    throw new \RuntimeException(
                        \sprintf(
                            'Saga with identifier "%s:%s" not exists. Please, use start() method for saga creation',
                            (string) $saga->id(),
                            \get_class($saga->id())
                        )
                    );
                }
                catch(\Throwable $throwable)
                {
                    throw new SaveSagaFailed($throwable->getMessage(), $throwable->getCode(), $throwable);
                }
            },
            $saga
        );
    }

    /**
     * @param SagasStore $store
     * @param SagaId     $id
     *
     * @return Promise<\Desperado\ServiceBus\Sagas\Saga|null>
     */
    private static function doLoad(SagasStore $store, SagaId $id): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(SagaId $id) use ($store): \Generator
            {
                $saga = null;

                /** @var StoredSaga|null $savedSaga */
                $savedSaga = yield $store->load($id);

                if(null !== $savedSaga)
                {
                    $saga = \unserialize(\base64_decode($savedSaga->payload()), ['allowed_classes' => true]);
                }

                return yield new Success($saga);
            },
            $id
        );
    }

    /**
     * Execute add/update saga entry
     *
     * @param SagasStore             $store
     * @param Saga                   $saga
     * @param MessageDeliveryContext $context
     * @param bool                   $isNew
     *
     * @psalm-suppress MoreSpecificReturnType Incorrect resolving the value of the promise
     * @psalm-suppress LessSpecificReturnStatement Incorrect resolving the value of the promise
     *
     * @return Promise<null>
     *
     * @throws \Desperado\ServiceBus\Storage\Exceptions\UniqueConstraintViolationCheckFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\ConnectionFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\OperationFailed
     * @throws \Desperado\ServiceBus\Storage\Exceptions\StorageInteractingFailed
     */
    private function doStore(SagasStore $store, Saga $saga, MessageDeliveryContext $context, bool $isNew): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(Saga $saga, MessageDeliveryContext $context, bool $isNew) use ($store): \Generator
            {
                $commands = invokeReflectionMethod($saga, 'firedCommands');
                $events   = invokeReflectionMethod($saga, 'raisedEvents');

                /** @var \DateTimeImmutable|null $closedAt */
                $closedAt = readReflectionPropertyValue($saga, 'closedAt');

                $savedSaga = StoredSaga::create(
                    $saga->id(),
                    $saga->status(),
                    \base64_encode(\serialize($saga)),
                    $saga->createdAt(),
                    $saga->expireDate(),
                    $closedAt
                );

                $contextHandler = static function() use ($context, $commands, $events): \Generator
                {
                    yield $context->delivery(... $commands);
                    yield $context->delivery(... $events);
                };

                true === $isNew
                    ? yield $store->save($savedSaga, $contextHandler)
                    : yield $store->update($savedSaga, $contextHandler);

                return yield new Success();
            },
            $saga,
            $context,
            $isNew
        );
    }
}
