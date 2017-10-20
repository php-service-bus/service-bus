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

use Desperado\CQRS\Context\HttpRequestContextInterface;
use Desperado\CQRS\MessageBus;
use Desperado\Domain\ContextInterface;
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\MessageBusInterface;
use Desperado\Domain\MessageRouterInterface;
use Desperado\Domain\Messages\AbstractQueryMessage;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\Domain\ThrowableFormatter;
use Desperado\Framework\Metrics\MetricsCollectorInterface;
use Desperado\Framework\StorageManager\FlushProcessor;
use Desperado\Framework\StorageManager\StorageManagerRegistry;
use Desperado\Infrastructure\Bridge\Router\Exceptions\HttpException;
use Psr\Log\LogLevel;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

/**
 * Application kernel
 */
abstract class AbstractKernel
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
     * Storage managers registry
     *
     * @var StorageManagerRegistry
     */
    private $storageManagersRegistry;

    /**
     * Message router
     *
     * @var MessageRouterInterface
     */
    private $messageRouter;

    /**
     * Message bus
     *
     * @var MessageBus
     */
    private $messageBus;

    /**
     * Metrics collector
     *
     * @var MetricsCollectorInterface
     */
    private $metricsCollector;

    /**
     * @param string                    $entryPointName
     * @param Environment               $environment
     * @param StorageManagerRegistry    $storageManagersRegistry
     * @param MessageRouterInterface    $messageRouter
     * @param MessageBusInterface       $messageBus
     * @param MetricsCollectorInterface $metricsCollector
     */
    public function __construct(
        string $entryPointName,
        Environment $environment,
        StorageManagerRegistry $storageManagersRegistry,
        MessageRouterInterface $messageRouter,
        MessageBusInterface $messageBus,
        MetricsCollectorInterface $metricsCollector
    )
    {
        $this->entryPointName = $entryPointName;
        $this->environment = $environment;
        $this->storageManagersRegistry = $storageManagersRegistry;
        $this->messageRouter = $messageRouter;
        $this->messageBus = $messageBus;
        $this->metricsCollector = $metricsCollector;
    }

    /**
     * Handle message
     *
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return PromiseInterface
     */
    final public function handle(MessageInterface $message, ContextInterface $context): PromiseInterface
    {
        $applicationContext = $this->createApplicationContext($context, $this->storageManagersRegistry);

        $rejectHandler = $this->getRejectPromiseHandler($message, $applicationContext);
        $flushHandler = $this->getFlushPromiseHandler($applicationContext);
        $finishedHandler = $this->getSuccessFinishedMessagePromiseHandler();

        return $this
            ->getMessageExecutionPromise($message, $applicationContext)
            ->then($flushHandler, $rejectHandler)
            ->then($finishedHandler, $rejectHandler);
    }

    /**
     * Create application context
     *
     * @param ContextInterface       $originContext
     * @param StorageManagerRegistry $storageManagersRegistry
     *
     * @return AbstractApplicationContext
     */
    abstract protected function createApplicationContext(
        ContextInterface $originContext,
        StorageManagerRegistry $storageManagersRegistry
    ): AbstractApplicationContext;

    /**
     * Get entry point name
     *
     * @return string
     */
    final protected function getEntryPointName(): string
    {
        return $this->entryPointName;
    }

    /**
     * Get environment
     *
     * @return Environment
     */
    final protected function getEnvironment(): Environment
    {
        return $this->environment;
    }

    /**
     * Get message router
     *
     * @return MessageRouterInterface
     */
    final protected function getMessageRouter(): MessageRouterInterface
    {
        return $this->messageRouter;
    }

    /**
     * Get get metrics collector
     *
     * @return MetricsCollectorInterface
     */
    final protected function getMetricsCollector(): MetricsCollectorInterface
    {
        return $this->metricsCollector;
    }

    /**
     * Get storage manager registry
     *
     * @return StorageManagerRegistry
     */
    final protected function getStorageManagersRegistry(): StorageManagerRegistry
    {
        return $this->storageManagersRegistry;
    }

    /**
     * Get success finished message execution promise handler
     *
     * @return callable
     */
    private function getSuccessFinishedMessagePromiseHandler(): callable
    {
        return function(float $timeStart)
        {
            $workTime = \microtime(true) - $timeStart;

            /** Save metrics */
            $this->metricsCollector->push(
                MetricsCollectorInterface::TYPE_FLUSH_WORK_TIME,
                $workTime
            );

            return true;
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
            $timeStart = \microtime(true);

            (new FlushProcessor($this->storageManagersRegistry))->process($context);

            return $timeStart;
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
            $context->logContextMessage(
                $message,
                ThrowableFormatter::toString($throwable),
                LogLevel::ERROR,
                [
                    'message' => \get_class($message),
                    'context' => \get_class($context),
                    'payload' => \json_encode(\get_object_vars($message))
                ]
            );

            return false;
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
    private function getMessageExecutionPromise(
        MessageInterface $message,
        AbstractApplicationContext $context
    ): PromiseInterface
    {
        return new Promise(
            function($resolve, $reject) use ($message, $context)
            {
                try
                {
                    $timeStart = \microtime(true);

                    /** Handle message */
                    $promise = $this->messageBus->handle($message, $context);

                    /** If null, then task for specified message was not configured */
                    if(null !== $promise)
                    {
                        $promise->then(
                            function() use ($resolve, $timeStart, $message)
                            {
                                try
                                {
                                    /** Save metrics */
                                    $this->metricsCollector->push(
                                        MetricsCollectorInterface::TYPE_HANDLE_WORK_TIME,
                                        \microtime(true) - $timeStart,
                                        ['message' => \get_class($message)]
                                    );
                                }
                                catch(\Throwable $throwable)
                                {
                                    unset($throwable);
                                }

                                $resolve();
                            },
                            function(\Throwable $throwable) use ($message, $reject, $context)
                            {
                                if(
                                    $message instanceof AbstractQueryMessage &&
                                    $throwable instanceof HttpException &&
                                    $context instanceof HttpRequestContextInterface
                                )
                                {
                                    $context->sendResponse(
                                        $message,
                                        $throwable->getHttpCode(),
                                        $throwable->getResponseMessage()
                                    );
                                }

                                $reject($throwable);
                            }
                        );
                    }
                    else
                    {
                        $resolve();
                    }
                }
                catch(\Throwable $throwable)
                {
                    $reject($throwable);
                }
            }
        );
    }
}
