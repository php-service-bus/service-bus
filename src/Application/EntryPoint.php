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

use Desperado\Domain\ContextInterface;
use Desperado\Domain\EntryPointInterface;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\MessageBusInterface;
use Desperado\Domain\MessageRouterInterface;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\Domain\Serializer\MessageSerializerInterface;
use Desperado\Framework\StorageManager\StorageManagerRegistry;

/**
 * Application entry point
 */
final class EntryPoint implements EntryPointInterface
{
    /**
     * Entry point name
     *
     * @var string
     */
    private $entryPointName;

    /**
     * Application environment
     *
     * @var Environment
     */
    private $environment;

    /**
     * Message serializer
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * Storage managers registry for aggregates/sagas
     *
     * @var StorageManagerRegistry
     */
    private $storageManagersRegistry;

    /**
     * Message bus
     *
     * @var MessageBusInterface
     */
    private $messageBus;

    /**
     * Message router
     *
     * @var MessageRouterInterface
     */
    private $messageRouter;

    /**
     * Application kernel
     *
     * @var AbstractKernel
     */
    private $kernel;

    /**
     * @param string                     $entryPointName
     * @param Environment                $environment
     * @param MessageSerializerInterface $messageSerializer
     * @param StorageManagerRegistry     $storageManagersRegistry
     * @param MessageBusInterface        $messageBus
     * @param MessageRouterInterface     $messageRouter
     * @param AbstractKernel             $kernel
     */
    public function __construct(
        string $entryPointName,
        Environment $environment,
        MessageSerializerInterface $messageSerializer,
        StorageManagerRegistry $storageManagersRegistry,
        MessageBusInterface $messageBus,
        MessageRouterInterface $messageRouter,
        AbstractKernel $kernel
    )
    {
        $this->entryPointName = $entryPointName;
        $this->environment = $environment;
        $this->messageSerializer = $messageSerializer;
        $this->storageManagersRegistry = $storageManagersRegistry;
        $this->messageBus = $messageBus;
        $this->messageRouter = $messageRouter;
        $this->kernel = $kernel;
    }

    /**
     * @inheritdoc
     */
    public function getEntryPointName(): string
    {
        return $this->entryPointName;
    }

    /**
     * @inheritdoc
     */
    public function getMessageSerializer(): MessageSerializerInterface
    {
        return $this->messageSerializer;
    }

    /**
     * @inheritdoc
     */
    public function handleMessage(MessageInterface $message, ContextInterface $context): void
    {
        $entryPointContext = new EntryPointContext(
            $context,
            $this->messageRouter,
            $this->storageManagersRegistry
        );

        $this->kernel->handleMessage($message, $entryPointContext);
    }
}
