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
use Desperado\Domain\ContextInterface;
use Desperado\Domain\MessageRouterInterface;
use Desperado\Domain\Messages\CommandInterface;
use Desperado\Domain\Messages\EventInterface;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\Framework\StorageManager\StorageManagerRegistry;

/**
 * Base entry point context
 */
class EntryPointContext implements DeliveryContextInterface
{
    /**
     * Origin context
     *
     * @var ContextInterface
     */
    private $originContext;

    /**
     * Message router
     *
     * @var MessageRouterInterface
     */
    private $messageRouter;

    /**
     * Storage managers registry for aggregates/sagas
     *
     * @var StorageManagerRegistry
     */
    private $storageManagersRegistry;

    /**
     * @param ContextInterface       $originContext
     * @param MessageRouterInterface $messageRouter
     * @param StorageManagerRegistry $storageManagersRegistry
     */
    public function __construct(
        ContextInterface $originContext,
        MessageRouterInterface $messageRouter,
        StorageManagerRegistry $storageManagersRegistry
    )
    {
        $this->originContext = $originContext;
        $this->messageRouter = $messageRouter;
        $this->storageManagersRegistry = $storageManagersRegistry;
    }

    /**
     * Get storage manager registry
     *
     * @return StorageManagerRegistry
     */
    public function getStorageManagersRegistry(): StorageManagerRegistry
    {
        return $this->storageManagersRegistry;
    }

    /**
     * @inheritdoc
     */
    public function send(CommandInterface $command, DeliveryOptions $deliveryOptions): void
    {
        $this->delivery($command, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function publish(EventInterface $event, DeliveryOptions $deliveryOptions): void
    {
        $this->delivery($event, $deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function delivery(MessageInterface $message, DeliveryOptions $deliveryOptions = null): void
    {
        if($this->originContext instanceof DeliveryContextInterface)
        {
            $this->originContext->delivery($message);
        }
        else
        {
            ApplicationLogger::debug(
                'entryPointContext',
                \sprintf(
                    'To send messages, original context should implement the interface "%s"',
                    DeliveryContextInterface::class
                ),
                ['originalContext' => \get_class($this->originContext)]
            );
        }
    }
}
