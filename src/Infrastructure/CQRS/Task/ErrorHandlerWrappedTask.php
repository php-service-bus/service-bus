<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\CQRS\Task;

use Desperado\Framework\Common\Formatter\ThrowableFormatter;
use Desperado\Framework\Domain\Context\ContextInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Domain\Task\TaskInterface;

/**
 * Handle error task
 */
class ErrorHandlerWrappedTask extends AbstractTask
{
    /**
     * Original task
     *
     * @var TaskInterface
     */
    private $originalTask;

    /**
     * Error handlers
     *
     * @var array
     */
    private $handlers = [];

    /**
     * @param TaskInterface $originalTask
     * @param array         $handlers
     */
    public function __construct(TaskInterface $originalTask, array $handlers)
    {
        $this->originalTask = $originalTask;
        $this->handlers = $handlers;

        parent::__construct($originalTask->getOptions());
    }

    /**
     * @inheritdoc
     */
    public function __invoke(MessageInterface $message, ContextInterface $context): ?TaskInterface
    {
        $this->appendOptions($context);

        try
        {
            $task = $this->originalTask;

            return $task($message, $context);
        }
        catch(\Exception $exception)
        {
            $this->handleException($exception, $message, $context);
        }
        catch(\Throwable $throwable)
        {
            $this->handleThrowable($throwable, $message, $context);
        }

        return null;
    }

    /**
     * Calling the error handler (if specified)
     *
     * @param \Exception       $exception
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return void
     */
    private function handleException(
        \Exception $exception,
        MessageInterface $message,
        ContextInterface $context
    ): void
    {
        $logger = $this->getLogger($message, $context);
        $exceptionClass = \get_class($exception);
        $messageClass = \get_class($message);
        $handler = $this->getErrorHandler($exceptionClass, $messageClass);

        $logMessage = \sprintf(
            'An exception "%s" (%s) was thrown during execution of the "%s" message%s',
            $exceptionClass, $exception->getMessage(), $messageClass,
            null !== $handler
                ? '. Error handler found'
                : '. The error handler was not found'
        );

        null !== $handler
            ? $logger->debug($logMessage)
            : $logger->error($logMessage);

        if(null !== $handler)
        {
            try
            {
                $handler($exception, $message, $context);

                $logger->debug(
                    \sprintf(
                        'Exception "%s" for message "%s" successful intercepted',
                        $exceptionClass, $messageClass
                    )
                );
            }
            catch(\Throwable $throwable)
            {
                $logger->critical(
                    \sprintf(
                        'The error handler can\'t throw an exception. Intercepted: %s',
                        ThrowableFormatter::toString($throwable)
                    )
                );
            }
        }
    }

    /**
     * Processing of an unrecovered exception
     *
     * @param \Throwable       $throwable
     * @param MessageInterface $message
     * @param ContextInterface $context
     *
     * @return void
     */
    private function handleThrowable(
        \Throwable $throwable,
        MessageInterface $message,
        ContextInterface $context
    ): void
    {
        $this
            ->getLogger($message, $context)
            ->critical(
                \sprintf(
                    'A fatal error (%s) was caught during the execution of the message "%s" with payload "%s"',
                    ThrowableFormatter::toString($throwable),
                    \get_class($message),
                    self::getMessagePayloadAsString($message)
                )
            );
    }

    /**
     * Get error handler for message
     *
     * @param string $exceptionNamespace
     * @param string $messageNamespace
     *
     * @return \Closure|null
     */
    private function getErrorHandler(string $exceptionNamespace, string $messageNamespace): ?\Closure
    {
        if(isset($this->handlers[$messageNamespace][$exceptionNamespace]))
        {
            return $this->handlers[$messageNamespace][$exceptionNamespace];
        }

        return null;
    }
}
