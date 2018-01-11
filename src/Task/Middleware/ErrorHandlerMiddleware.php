<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Task\Middleware;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Desperado\ServiceBus\Task\TaskInterface;
use React\Promise\PromiseInterface;
use React\Promise\RejectedPromise;

/**
 * Handling of non-intercepted exceptions
 */
class ErrorHandlerMiddleware implements TaskInterface
{
    /**
     * Original task
     *
     * @var TaskInterface
     */
    private $task;

    /**
     * Error handlers
     *
     * @var Handlers\Exceptions\ExceptionHandlersCollection
     */
    private $exceptionHandlersCollection;

    /**
     * @param TaskInterface                                   $task
     * @param Handlers\Exceptions\ExceptionHandlersCollection $exceptionHandlersCollection
     */
    public function __construct(
        TaskInterface $task,
        Handlers\Exceptions\ExceptionHandlersCollection $exceptionHandlersCollection
    )
    {
        $this->task = $task;
        $this->exceptionHandlersCollection = $exceptionHandlersCollection;
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): Handlers\Messages\AbstractMessageExecutionParameters
    {
        return $this->task->getOptions();
    }

    /**
     * @inheritdoc
     */
    public function __invoke(
        AbstractMessage $message,
        AbstractExecutionContext $context,
        array $additionalArguments = []
    ): PromiseInterface
    {
        try
        {
            return \call_user_func_array($this->task, [$message, $context, $additionalArguments]);
        }
        catch(\Exception $exception)
        {
            return $this->handleException($exception, $message, $context);
        }
    }

    /**
     * Calling the error handler (if specified)
     *
     * @param \Exception               $exception
     * @param AbstractMessage          $message
     * @param AbstractExecutionContext $context
     *
     * @return PromiseInterface
     *
     * @throws \Throwable
     */
    private function handleException(
        \Exception $exception,
        AbstractMessage $message,
        AbstractExecutionContext $context
    ): PromiseInterface
    {
        $exceptionClass = \get_class($exception);
        $messageClass = \get_class($message);

        $handler = $this->exceptionHandlersCollection->searchHandler($messageClass, $exceptionClass);

        $logMessage = \sprintf(
            'An exception "%s" (%s %s:%d) was thrown during execution of the "%s" message%s',
            $exceptionClass, $exception->getMessage(), $exception->getFile(), $exception->getLine(), $messageClass,
            null !== $handler
                ? '. Error handler found'
                : '. The error handler was not found'
        );

        $context->logContextMessage($logMessage);

        if(null !== $handler)
        {
            return $handler(
                Handlers\Exceptions\UnfulfilledPromiseData::create(
                    $exception, $message, $context
                )
            );
        }

        return new RejectedPromise($exception);
    }
}
