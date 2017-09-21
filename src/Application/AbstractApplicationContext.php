<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Application;

use Desperado\CQRS\Context\DeliveryContextInterface;
use Desperado\CQRS\Context\DeliveryOptions;
use Desperado\Domain\Messages\CommandInterface;
use Desperado\Domain\Messages\EventInterface;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\EventSourcing\AggregateStorageManagerInterface;
use Desperado\EventSourcing\Saga\SagaStorageManagerInterface;
use Desperado\Framework\Exceptions\ApplicationContextException;

/**
 * Base application context
 */
class AbstractApplicationContext implements DeliveryContextInterface
{
    /**
     * Entry point context
     *
     * @var EntryPointContext
     */
    private $entryPointContext;

    /**
     * @param EntryPointContext $entryPointContext
     */
    public function __construct(EntryPointContext $entryPointContext)
    {
        $this->entryPointContext = $entryPointContext;
    }

    /**
     * Get manager for specified aggregate
     *
     * @param string $aggregateNamespace
     *
     * @return AggregateStorageManagerInterface
     */
    public function getAggregateManager(string $aggregateNamespace): AggregateStorageManagerInterface
    {
        $manager = $this->entryPointContext
            ->getStorageManagersRegistry()
            ->getAggregateManager($aggregateNamespace);

        if(null !== $manager)
        {
            return $manager;
        }

        throw new ApplicationContextException(
            \sprintf(
                'The manager for aggregate "%s" was not configured', $aggregateNamespace
            )
        );
    }

    /**
     * Get manager for specified saga
     *
     * @param string $sagaNamespace
     *
     * @return SagaStorageManagerInterface
     */
    public function getSagaManager(string $sagaNamespace): SagaStorageManagerInterface
    {
        $manager = $this->entryPointContext
            ->getStorageManagersRegistry()
            ->getSagaManager($sagaNamespace);

        if(null !== $manager)
        {
            return $manager;
        }

        throw new ApplicationContextException(
            \sprintf(
                'The manager for saga "%s" was not configured', $sagaNamespace
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void
    {
        $this->entryPointContext->delivery($command, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function delivery(MessageInterface $message, DeliveryOptions $deliveryOptions = null): void
    {
        $this->entryPointContext->delivery($message, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void
    {
        $this->entryPointContext->publish($event, $deliveryOptions);
    }
}
