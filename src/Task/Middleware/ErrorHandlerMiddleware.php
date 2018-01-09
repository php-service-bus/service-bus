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
use Desperado\Domain\ThrowableFormatter;
use Desperado\Infrastructure\Bridge\Router\Exceptions\HttpException;
use Desperado\ServiceBus\Services\Handlers;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Desperado\ServiceBus\Task\TaskInterface;
use Psr\Log\LogLevel;

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
    public function __invoke(AbstractMessage $message, AbstractExecutionContext $context): ?TaskInterface
    {
        try
        {
            return \call_user_func_array($this->task, [$message, $context]);
        }
        catch(HttpException $httpException)
        {
            throw $httpException;
        }
        catch(\Exception $exception)
        {
            $this->handleException($exception, $message, $context);
        }
        catch(\Throwable $throwable)
        {
            throw $throwable;
        }

        return null;
    }

    /**
     * Calling the error handler (if specified)
     *
     * @param \Exception               $exception
     * @param AbstractMessage          $message
     * @param AbstractExecutionContext $context
     *
     * @return void
     *
     * @throws \Throwable
     */
    private function handleException(
        \Exception $exception,
        AbstractMessage $message,
        AbstractExecutionContext $context
    ): void
    {
        $exceptionClass = \get_class($exception);
        $messageClass = \get_class($message);
        $handler = $this->exceptionHandlersCollection->searchHandler($exceptionClass, $messageClass);

        $logMessage = \sprintf(
            'An exception "%s" (%s %s:%d) was thrown during execution of the "%s" message%s',
            $exceptionClass, $exception->getMessage(), $exception->getFile(), $exception->getLine(), $messageClass,
            null !== $handler
                ? '. Error handler found'
                : '. The error handler was not found'
        );

        null !== $handler
            ? $context->logContextMessage($message, $logMessage, LogLevel::INFO)
            : $context->logContextMessage($message, $logMessage, LogLevel::ERROR);

        if(null !== $handler)
        {
            try
            {
                $handler($exception, $message, $context);

                $context->logContextMessage(
                    $message,
                    \sprintf(
                        'Exception "%s" for message "%s" successful intercepted',
                        $exceptionClass, $messageClass
                    ),
                    LogLevel::INFO
                );
            }
            catch(\Throwable $throwable)
            {
                $context->logContextMessage(
                    $message,
                    \sprintf(
                        'The error handler can\'t throw an exception. Intercepted: %s',
                        ThrowableFormatter::toString($throwable)
                    ),
                    LogLevel::ALERT
                );
            }
        }
        else
        {
            throw $exception;
        }
    }
}
