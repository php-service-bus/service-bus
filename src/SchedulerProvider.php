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
use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Common\ExecutionContext\LoggingInContext;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Desperado\ServiceBus\Scheduler\Data\ScheduledOperation;
use Desperado\ServiceBus\Scheduler\Messages\Event\OperationScheduled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationCanceled;
use Desperado\ServiceBus\Scheduler\Messages\Event\SchedulerOperationEmitted;
use Desperado\ServiceBus\Scheduler\ScheduledOperationId;
use Desperado\ServiceBus\Scheduler\Store\SchedulerStore;

/**
 *
 */
final class SchedulerProvider
{
    /**
     * @var SchedulerStore
     */
    private $store;

    /**
     * @param SchedulerStore $store
     */
    public function __construct(SchedulerStore $store)
    {
        $this->store = $store;
    }

    /**
     * Schedule operation
     *
     * @param ScheduledOperationId   $id
     * @param Command                $command
     * @param \DateTimeImmutable     $executionDate
     * @param MessageDeliveryContext $context
     *
     * @return Promise<null>
     */
    public function schedule(
        ScheduledOperationId $id,
        Command $command,
        \DateTimeImmutable $executionDate,
        MessageDeliveryContext $context
    ): Promise
    {
        $store = $this->store;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperation $operation) use ($store, $context): \Generator
            {
                /** @var \Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation|null $nextOperation */
                $nextOperation = yield $store->loadNextOperation();

                yield $store->add($operation);
                
                yield $context->delivery(
                    OperationScheduled::create(
                        $operation->id(),
                        $operation->command(),
                        $operation->date(),
                        $nextOperation
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
        $store = $this->store;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperationId $id, ?string $reason = null) use ($store, $context): \Generator
            {
                yield $store->remove($id);

                /** @var \Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation|null $nextOperation */
                $nextOperation = yield $store->loadNextOperation();

                yield $context->delivery(
                    SchedulerOperationCanceled::create($id, $reason, $nextOperation)
                );
            },
            $id, $reason
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
        $store = $this->store;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(ScheduledOperationId $id) use ($store, $context): \Generator
            {
                $handler = static function(ScheduledOperation $operation) use ($context): \Generator
                {
                    yield $context->delivery($operation->command());
                };

                /** @var \Desperado\ServiceBus\Scheduler\Data\ScheduledOperation|null $operation */
                $operation = yield $store->extract($id, $handler);

                if(null !== $operation && true === $context instanceof LoggingInContext)
                {
                    /** @var LoggingInContext $context */

                    $context->logContextMessage(
                        'The delayed "{messageClass}" command has been sent to the transport', [
                            'messageClass'         => \get_class($operation->command()),
                            'scheduledOperationId' => (string) $operation->id()
                        ]
                    );
                }

                /** @var \Desperado\ServiceBus\Scheduler\Data\NextScheduledOperation|null $nextOperation */
                $nextOperation = yield $store->loadNextOperation();

                yield $context->delivery(
                    SchedulerOperationEmitted::create($id, $nextOperation)
                );
            },
            $id
        );
    }
}
