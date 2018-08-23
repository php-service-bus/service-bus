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
use function Desperado\ServiceBus\Common\datetimeInstantiator;
use Desperado\ServiceBus\Common\ExecutionContext\LoggingInContext;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Desperado\ServiceBus\Scheduler\Data\ScheduledOperation;
use Desperado\ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate;
use Desperado\ServiceBus\Scheduler\Messages\Event\OperationScheduled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationCanceled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationEmitted;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;
use Desperado\ServiceBus\Scheduler\Store\SchedulerRegistry;
use Desperado\ServiceBus\Scheduler\Store\SchedulerStore;
use Ramsey\Uuid\Uuid;

/**
 *
 */
final class SchedulerProvider
{
    /**
     * Registry identifier
     *
     * @var string
     */
    private $registryId;

    /**
     * @var SchedulerStore
     */
    private $store;

    /**
     * @param SchedulerStore $store
     */
    public function __construct(SchedulerStore $store, string $registryName = 'scheduler_registry')
    {
        $this->registryId = Uuid::uuid5(SchedulerRegistry::class, $registryName)->toString();
        $this->store      = $store;
    }

    /**
     * Schedule operation
     *
     * @param ScheduledOperationId   $id
     * @param Command                $command
     * @param \DateTimeImmutable     $executionDate
     * @param MessageDeliveryContext $context
     *
     * @return Promise
     */
    public function schedule(
        ScheduledOperationId $id,
        Command $command,
        \DateTimeImmutable $executionDate,
        MessageDeliveryContext $context
    ): Promise
    {
        self::guardOperationExecutionDate($executionDate);

        $store      = $this->store;
        $registryId = $this->registryId;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperation $operation) use ($store, $registryId, $context): \Generator
            {
                /** @var SchedulerRegistry $registry */
                $registry = yield self::obtainRegistry($store, $registryId);

                $registry->add($operation);

                yield self::updateRegistry($store, $registry, false);

                yield $context->delivery(
                    OperationScheduled::create(
                        $operation->id(),
                        $operation->command(),
                        $operation->date(),
                        $registry->fetchNextOperation()
                    )
                );
            },
            new ScheduledOperation($id, $command, $executionDate)
        );
    }

    /**
     * Cancel scheduled operation
     *
     * @param ScheduledOperationId   $id
     * @param MessageDeliveryContext $context
     *
     * @return Promise<null>
     */
    public function cancel(ScheduledOperationId $id, MessageDeliveryContext $context, ?string $reason = null): Promise
    {
        $store      = $this->store;
        $registryId = $this->registryId;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperationId $id, ?string $reason) use ($store, $registryId, $context): \Generator
            {
                /** @var SchedulerRegistry $registry */
                $registry = yield self::obtainRegistry($store, $registryId);

                $registry->remove($id);

                yield self::updateRegistry($store, $registry, false);

                yield $context->delivery(
                    SchedulerOperationCanceled::create(
                        $id,
                        $reason,
                        $registry->fetchNextOperation()
                    )
                );
            },
            $id,
            $reason
        );
    }

    /**
     * Emit operation
     * Called by infrastructure components
     *
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @param ScheduledOperationId   $operationId
     * @param MessageDeliveryContext $context
     *
     * @return Promise<null>
     */
    private function emit(ScheduledOperationId $id, MessageDeliveryContext $context): Promise
    {
        $store      = $this->store;
        $registryId = $this->registryId;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperationId $id) use ($store, $registryId, $context): \Generator
            {
                /** @var SchedulerRegistry $registry */
                $registry = yield self::obtainRegistry($store, $registryId);

                $operation = $registry->extract($id);

                if(null !== $operation)
                {
                    $command = $operation->command();

                    yield self::updateRegistry($store, $registry, false);
                    yield $context->delivery($command);

                    if($context instanceof LoggingInContext)
                    {
                        $context->logContextMessage(
                            'The delayed "{messageClass}" command has been sent to the transport', [
                                'messageClass'         => \get_class($command),
                                'scheduledOperationId' => (string) $id
                            ]
                        );
                    }
                }

                yield $context->delivery(
                    SchedulerOperationEmitted::create($id, $registry->fetchNextOperation())
                );
            },
            $id
        );
    }

    /**
     * Obtain registry
     *
     * @param SchedulerStore $store
     * @param string         $registryId
     *
     * @return Promise<\Desperado\ServiceBus\Scheduler\Store\SchedulerRegistry>
     */
    private static function obtainRegistry(SchedulerStore $store, string $registryId): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(string $registryId) use ($store): \Generator
            {
                /** @var SchedulerRegistry|null $registry */
                $registry = yield $store->load($registryId);

                if(null === $registry)
                {
                    $registry = SchedulerRegistry::create($registryId);

                    yield self::updateRegistry($store, $registry, true);
                }

                return yield new Success($registry);
            },
            $registryId
        );
    }

    /**
     * Update\Save new registry
     *
     * @param SchedulerStore    $store
     * @param SchedulerRegistry $registry
     *
     * @return Promise<null>
     */
    private static function updateRegistry(SchedulerStore $store, SchedulerRegistry $registry, bool $isNew = false): Promise
    {
        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(SchedulerRegistry $registry, bool $isNew) use ($store): \Generator
            {
                true === $isNew
                    ? yield $store->add($registry)
                    : yield $store->update($registry);

                return yield new Success();
            },
            $registry,
            $isNew
        );
    }

    /**
     * Make sure that the specific date of the operation is specified
     *
     * @param \DateTimeImmutable $dateTime
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Scheduler\Exceptions\InvalidScheduledOperationExecutionDate
     */
    private static function guardOperationExecutionDate(\DateTimeImmutable $dateTime): void
    {
        /** @var \DateTimeImmutable $currentDate */
        $currentDate = datetimeInstantiator('NOW');

        if($currentDate->getTimestamp() > $dateTime->getTimestamp())
        {
            throw new InvalidScheduledOperationExecutionDate(
                'Scheduled operation date must be greater then current'
            );
        }
    }
}
