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

use Desperado\Domain\Messages\MessageInterface;
use Desperado\Framework\StorageManager\FlushProcessor;
use Desperado\Framework\StorageManager\StorageManagerRegistry;
use EventLoop\EventLoop;
use Psr\Log\LogLevel;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Application kernel
 */
abstract class AbstractKernel
{
    /**
     * Entry point
     *
     * @var EntryPoint
     */
    private $entryPoint;

    /**
     * Storage managers registry for aggregates/sagas
     *
     * @var StorageManagerRegistry
     */
    private $storageManagersRegistry;

    /**
     * @param EntryPoint             $entryPoint
     * @param StorageManagerRegistry $storageManagersRegistry
     */
    public function __construct(EntryPoint $entryPoint, StorageManagerRegistry $storageManagersRegistry)
    {
        $this->entryPoint = $entryPoint;
        $this->storageManagersRegistry = $storageManagersRegistry;
    }

    /**
     * Create application context
     *
     * @param EntryPointContext      $context
     * @param StorageManagerRegistry $storageManagersRegistry
     *
     * @return AbstractApplicationContext
     */
    abstract public function createApplicationContext(
        EntryPointContext $context,
        StorageManagerRegistry $storageManagersRegistry
    ): AbstractApplicationContext;

    /**
     * Handle message
     *
     * @param MessageInterface  $message
     * @param EntryPointContext $context
     *
     * @return PromiseInterface
     */
    public function handleMessage(MessageInterface $message, EntryPointContext $context): PromiseInterface
    {
        $applicationContext = $this->createApplicationContext($context, $this->storageManagersRegistry);

        $rejectHandler = $this->getRejectPromiseHandler($message, $applicationContext);
        $flushHandler = $this->getFlushPromiseHandler($applicationContext);
        $processingHandler = $this->getMessageProcessingPromiseHandler($message);
        $finishedHandler = $this->getSuccessFinishedMessagePromiseHandler($message);

        return $this
            ->getMessageExecutionPromise($message, $applicationContext)
            ->then($flushHandler, $rejectHandler)
            ->then($finishedHandler, $rejectHandler, $processingHandler);
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
     * @param AbstractApplicationContext $context
     *
     * @return callable
     */
    private function getFlushPromiseHandler(AbstractApplicationContext $context): callable
    {
        return function() use ($context)
        {
            return (new FlushProcessor($this->storageManagersRegistry))->process($context);
        };
    }

    /**
     * Get reject promise handler
     *
     * @param MessageInterface           $message
     * @param AbstractApplicationContext $context
     *
     * @return callable
     */
    private function getRejectPromiseHandler(MessageInterface $message, AbstractApplicationContext $context): callable
    {
        return function(\Throwable $throwable) use ($message, $context)
        {
            ApplicationLogger::throwable(
                $this->getLogChannelName(),
                $throwable,
                LogLevel::ERROR,
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
     * @param MessageInterface           $message
     * @param AbstractApplicationContext $context
     *
     * @return PromiseInterface
     */
    private function getMessageExecutionPromise(MessageInterface $message, AbstractApplicationContext $context): PromiseInterface
    {
        return new Promise(
            function($resolve, $reject) use ($message, $context)
            {
                EventLoop::getLoop()->futureTick(
                    function() use ($resolve, $reject, $message, $context)
                    {
                        try
                        {
                            $this->entryPoint->handleMessage($message, $context);

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
        return \sprintf('%sEntryPoint', $this->entryPoint->getEntryPointName());
    }
}
