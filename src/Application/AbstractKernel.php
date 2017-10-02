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
use Desperado\Domain\Environment\Environment;
use Desperado\Domain\MessageBusInterface;
use Desperado\Domain\MessageRouterInterface;
use Desperado\Domain\Messages\MessageInterface;
use Desperado\Domain\ThrowableFormatter;
use Desperado\Framework\Metrics\MetricsCollectorInterface;
use Desperado\Framework\StorageManager\FlushProcessor;
use Desperado\Framework\StorageManager\StorageManagerRegistry;
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
     * @var MessageBusInterface
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
        $processingHandler = $this->getMessageProcessingPromiseHandler($message, $applicationContext);
        $finishedHandler = $this->getSuccessFinishedMessagePromiseHandler($message, $applicationContext);

        return $this
            ->getMessageExecutionPromise($message, $applicationContext)
            ->then($flushHandler, $rejectHandler)
            ->then($finishedHandler, $rejectHandler, $processingHandler);
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
     * Get storage manager registry
     *
     * @return StorageManagerRegistry
     */
    final protected function getStorageManagersRegistry(): StorageManagerRegistry
    {
        return $this->storageManagersRegistry;
    }

    /**
     * Get finished message execution handler
     *
     * @param MessageInterface           $message
     * @param AbstractApplicationContext $context
     *
     * @return callable
     */
    private function getMessageProcessingPromiseHandler(
        MessageInterface $message,
        AbstractApplicationContext $context
    ): callable
    {
        return function() use ($message, $context)
        {

        };
    }

    /**
     * Get success finished message execution promise handler
     *
     * @param MessageInterface           $message
     * @param AbstractApplicationContext $context
     *
     * @return callable
     */
    private function getSuccessFinishedMessagePromiseHandler(
        MessageInterface $message,
        AbstractApplicationContext $context
    ): callable
    {
        return function(array $metricsData) use ($message, $context)
        {
            [$timeStart, $memoryUsageBytesOnStart, $tags] = $metricsData;

            $workTime = \microtime(true) - $timeStart;
            $memoryUsage = self::formatBytes(\memory_get_usage() - $memoryUsageBytesOnStart);

            /** Save metrics */
            $this->metricsCollector->push(
                MetricsCollectorInterface::TYPE_FLUSH_MEMORY_USAGE,
                $memoryUsage,
                $tags
            );

            $this->metricsCollector->push(
                MetricsCollectorInterface::TYPE_FLUSH_WORK_TIME,
                $workTime,
                $tags
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
            $tags = ['environment' => (string) $this->environment];
            $timeStart = \microtime(true);
            $memoryUsageBytesOnStart = \memory_get_usage();

            (new FlushProcessor($this->storageManagersRegistry))->process($context);

            return [$timeStart, $memoryUsageBytesOnStart, $tags];
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
                    $tags = ['message' => \get_class($message), 'environment' => (string) $this->environment];

                    $timeStart = \microtime(true);
                    $memoryUsageBytesOnStart = \memory_get_usage();

                    /** Handle message */
                    $this->messageBus->handle($message, $context);

                    $workTime = \microtime(true) - $timeStart;
                    $memoryUsage = self::formatBytes(\memory_get_usage() - $memoryUsageBytesOnStart);

                    /** Save metrics */
                    $this->metricsCollector->push(
                        MetricsCollectorInterface::TYPE_HANDLE_MEMORY_USAGE,
                        $memoryUsage,
                        $tags
                    );

                    $this->metricsCollector->push(
                        MetricsCollectorInterface::TYPE_HANDLE_WORK_TIME,
                        $workTime,
                        $tags
                    );

                    /** Resolve promise */
                    $resolve();
                }
                catch(\Throwable $throwable)
                {
                    $reject($throwable);
                }
            }
        );
    }

    /**
     * Format bytes
     *
     * @param int $bytes
     *
     * @return string
     */
    private static function formatBytes(int $bytes)
    {
        $bytes = 0 < $bytes ? (int) $bytes : 0;

        if($bytes > 1024 * 1024)
        {
            return \round($bytes / 1024 / 1024, 2) . ' MB';
        }
        else if($bytes > 1024)
        {
            return \round($bytes / 1024, 2) . ' kb';
        }

        return $bytes . ' b';
    }
}
