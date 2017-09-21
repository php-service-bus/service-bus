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
use Desperado\Framework\StorageManager\StorageManagerRegistry;

/**
 * Base application context
 */
abstract class AbstractApplicationContext implements DeliveryContextInterface
{
    /**
     * Origin context
     *
     * @var DeliveryContextInterface
     */
    private $originContext;

    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Storage managers registry for aggregates/sagas
     *
     * @var StorageManagerRegistry
     */
    private $storageManagersRegistry;

    /**
     * @param DeliveryContextInterface $originContext
     * @param string                   $entryPointName
     * @param StorageManagerRegistry   $storageManagersRegistry
     */
    public function __construct(
        DeliveryContextInterface $originContext,
        string $entryPointName,
        StorageManagerRegistry $storageManagersRegistry
    )
    {
        $this->originContext = $originContext;
        $this->entryPointName = $entryPointName;
        $this->storageManagersRegistry = $storageManagersRegistry;
    }

    /**
     * @inheritdoc
     */
    final public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void
    {
        $this->originContext->delivery($command, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    final public function delivery(MessageInterface $message, DeliveryOptions $deliveryOptions = null): void
    {
        $this->originContext->delivery($message, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    final public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void
    {
        $this->originContext->publish($event, $deliveryOptions);
    }

    /**
     * Get origin context
     *
     * @return DeliveryContextInterface
     */
    final protected function getOriginContext(): DeliveryContextInterface
    {
        return $this->originContext;
    }

    /**
     * Get entry point name
     *
     * @return string
     */
    final  protected function getEntryPointName()
    {
        return $this->entryPointName;
    }

    /**
     * Get storage manager registry
     *
     * @return StorageManagerRegistry
     */
    final  protected function getStorageManagersRegistry()
    {
        return $this->storageManagersRegistry;
    }
}
