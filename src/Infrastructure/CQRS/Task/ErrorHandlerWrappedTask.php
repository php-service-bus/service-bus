<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Task;

use Desperado\ConcurrencyFramework\Application\Context\KernelContext;
use Desperado\ConcurrencyFramework\Common\Formatter\ThrowableFormatter;
use Desperado\ConcurrencyFramework\Domain\Context\ContextInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Task\TaskInterface;

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
        try
        {
            $task = $this->originalTask;

            return $task($message, $context);
        }
        catch(\Exception $exception)
        {
            $logger = $this->getLogger($message, $context);

            /** @var KernelContext $context */
            $options = $context->getOptions($message);

            $logMessage = \sprintf(
                'An exception "%s" was thrown during execution of the "%s" message',
                \get_class($exception), \get_class($message)
            );

            if(true === $options->getLogPayloadFlag())
            {
                $logMessage .= \sprintf(' with payload "%s"', self::getMessagePayloadAsString($message));
            }

            $handlerData = $this->getErrorHandler($exception, $message);

            if(null !== $handlerData)
            {
                $logMessage .= '. Error handler found';

                $logger->debug($logMessage);

                try
                {
                    /** @var \Closure $handler */
                    $handler = $handlerData['handler'];

                    $handler($exception, $message, $context);

                    $logger->debug(
                        \sprintf(
                            'Exception "%s" for message "%s" successful intercepted',
                            \get_class($exception), \get_class($message)
                        )
                    );
                }
                catch(\Throwable $throwable)
                {
                    $logger->critical(
                        \sprintf(
                            'The error handler can not throw an exception. Intercepted: %s',
                            ThrowableFormatter::toString($throwable)
                        )
                    );
                }
            }
            else
            {
                $logMessage .= '. No error handler found';

                $logger->error($logMessage);
            }
        }
        catch(\Throwable $throwable)
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

        return null;
    }

    /**
     * Get error handler for message
     *
     * @param \Exception       $exception
     * @param MessageInterface $message
     *
     * @return array|null
     */
    private function getErrorHandler(\Exception $exception, MessageInterface $message): ?array
    {
        if(isset($this->handlers[\get_class($message)][\get_class($exception)]))
        {
            return $this->handlers[\get_class($message)][\get_class($exception)];
        }

        return null;
    }
}
