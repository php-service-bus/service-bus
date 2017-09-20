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

use Desperado\CQRS\Context\LocalDeliveryContext;
use Desperado\Domain\ContextInterface;
use Desperado\Domain\EntryPointInterface;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\MessageBusInterface;
use Desperado\Domain\MessageRouterInterface;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\Domain\Serializer\MessageSerializerInterface;
use Desperado\Framework\StorageManager\FlushProcessor;
use Desperado\Framework\StorageManager\StorageManagerRegistry;
use EventLoop\EventLoop;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

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
     * @param string                     $entryPointName
     * @param Environment                $environment
     * @param MessageSerializerInterface $messageSerializer
     * @param StorageManagerRegistry     $storageManagersRegistry
     * @param MessageBusInterface        $messageBus
     * @param MessageRouterInterface     $messageRouter
     */
    public function __construct(
        string $entryPointName,
        Environment $environment,
        MessageSerializerInterface $messageSerializer,
        StorageManagerRegistry $storageManagersRegistry,
        MessageBusInterface $messageBus,
        MessageRouterInterface $messageRouter
    )
    {
        $this->entryPointName = $entryPointName;
        $this->environment = $environment;
        $this->messageSerializer = $messageSerializer;
        $this->storageManagersRegistry = $storageManagersRegistry;
        $this->messageBus = $messageBus;
        $this->messageRouter = $messageRouter;
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
        /** Disable sending messages in a test environment */
        if(true === $this->environment->isTesting())
        {
            $context = new LocalDeliveryContext();
        }



        $rejectHandler = $this->getRejectPromiseHandler($message, $entryPointContext);
        $flushHandler = $this->getFlushPromiseHandler($entryPointContext);
        $processingHandler = $this->getMessageProcessingPromiseHandler($message);
        $finishedHandler = $this->getSuccessFinishedMessagePromiseHandler($message);

        $this
            ->getMessageExecutionPromise($message, $entryPointContext)
            ->then($flushHandler, $rejectHandler)
            ->then($finishedHandler, $rejectHandler, $processingHandler);
    }

    public function applyApplicationContext(ApplicationContext $applicationContext)
    {

    }

    /**
     * Get finished message execution handler
     *
     * @param MessageInterface $message
     *
     * @return callable
     */
    private function getMessageProcessingPromiseHandler(MessageInterface $message): callable
    {
        return function() use ($message)
        {
            ApplicationLogger::debug(
                $this->getLogChannelName(),
                \sprintf('Message "%s" is still in process', \get_class($message))
            );
        };
    }

    /**
     * Get success finished message execution promise handler
     *
     * @param MessageInterface $message
     *
     * @return callable
     */
    private function getSuccessFinishedMessagePromiseHandler(MessageInterface $message): callable
    {
        return function() use ($message)
        {
            ApplicationLogger::debug(
                $this->getLogChannelName(),
                \sprintf('Message "%s" execution complete', \get_class($message))
            );
        };
    }

    /**
     * Get flush storage managers handler
     *
     * @param ContextInterface $context
     *
     * @return callable
     */
    private function getFlushPromiseHandler(ContextInterface $context): callable
    {
        return function() use ($context)
        {
            return (new FlushProcessor($this->storageManagersRegistry))->process($context);
        };
    }

    /**
     * Get reject promise handler
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return callable
     */
    private function getRejectPromiseHandler(MessageInterface $message, ContextInterface $context): callable
    {
        return function(\Throwable $throwable) use ($message, $context)
        {
            ApplicationLogger::throwable(
                $this->getLogChannelName(),
                $throwable,
                [
                    'message' => \get_class($message),
                    'payload' => \json_encode(\get_object_vars($message)),
                    'context' => \get_class($context)
                ]
            );
        };
    }

    /**
     * Create handle message promise
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return PromiseInterface
     */
    private function getMessageExecutionPromise(MessageInterface $message, ContextInterface $context): PromiseInterface
    {
        return new Promise(
            function($resolve, $reject) use ($message, $context)
            {
                EventLoop::getLoop()->futureTick(
                    function() use ($resolve, $reject, $message, $context)
                    {
                        try
                        {
                            $this->messageBus->handle($message, $context);

                            $resolve();
                        }
                        catch(\Throwable $throwable)
                        {
                            $reject($throwable);
                        }
                    }
                );
            }
        );
    }

    /**
     * Get log channel name
     *
     * @return string
     */
    private function getLogChannelName(): string
    {
        return \sprintf('%sEntryPoint', $this->entryPointName);
    }
}
