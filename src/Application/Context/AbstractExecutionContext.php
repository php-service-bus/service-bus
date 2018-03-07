<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Context;

use Desperado\Domain\DateTime;
use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\ThrowableFormatter;
use Desperado\ServiceBus\Application\Context\Exceptions\CancelScheduledCommandFailedException;
use Desperado\ServiceBus\Application\Context\Exceptions\OutboundContextNotAppliedException;
use Desperado\ServiceBus\Transport\Message\MessageDeliveryOptions;
use Desperado\ServiceBus\Application\Context\Exceptions\ScheduleCommandFailedException;
use Desperado\ServiceBus\HttpServer\Context\OutboundHttpContextInterface;
use Desperado\ServiceBus\HttpServer\HttpResponse;
use Desperado\ServiceBus\Scheduler\Identifier\ScheduledCommandIdentifier;
use Desperado\ServiceBus\Scheduler\SchedulerProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Base class of the context of message processing
 */
abstract class AbstractExecutionContext implements ExecutionContextInterface
{
    /**
     * Scheduler provider
     *
     * @var SchedulerProvider
     */
    private $schedulerProvider;

    /**
     * @param SchedulerProvider $schedulerProvider
     */
    public function __construct(SchedulerProvider $schedulerProvider)
    {
        $this->schedulerProvider = $schedulerProvider;
    }

    /**
     * Bind http response
     *
     * @param HttpResponse $response
     *
     * @return void
     */
    final public function bindResponse(HttpResponse $response): void
    {
        $outboundMessageContext = $this->getOutboundMessageContext();

        if(
            $outboundMessageContext instanceof OutboundHttpContextInterface &&
            true === $outboundMessageContext->httpSessionStarted()
        )
        {
            $outboundMessageContext->bindResponse($response);
        }
    }

    /**
     * Schedule the task at the specified time
     *
     * @param ScheduledCommandIdentifier $id
     * @param AbstractCommand            $command
     * @param DateTime                   $delay
     *
     * @return void
     *
     * @throws ScheduleCommandFailedException
     */
    final public function scheduleCommand(ScheduledCommandIdentifier $id, AbstractCommand $command, DateTime $delay): void
    {
        try
        {
            $this->schedulerProvider->scheduleCommand($id, $command, $delay, $this);

            $this
                ->getLogger('scheduler')
                ->debug(
                    \sprintf('The execution of the command "%s" is postponed until "%s"',
                        $command->getMessageClass(), $delay->toString()
                    )
                );
        }
        catch(\Throwable $throwable)
        {
            throw new ScheduleCommandFailedException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * Cancel scheduled task execution
     *
     * @param ScheduledCommandIdentifier $id
     * @param string|null                $reason
     *
     * @return void
     *
     * @throws CancelScheduledCommandFailedException
     */
    final public function cancelScheduledCommand(ScheduledCommandIdentifier $id, ?string $reason = null): void
    {
        try
        {
            $this->schedulerProvider->cancelScheduledCommand($id, $this, $reason);
        }
        catch(\Throwable $throwable)
        {
            throw new CancelScheduledCommandFailedException($throwable->getMessage(), 0, $throwable);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws \Desperado\Domain\MessageSerializer\Exceptions\MessageSerializationFailException
     * @throws OutboundContextNotAppliedException
     */
    final public function delivery(AbstractMessage $message, MessageDeliveryOptions $messageDeliveryOptions = null): void
    {
        $messageDeliveryOptions = $messageDeliveryOptions ?? MessageDeliveryOptions::create();

        $message instanceof AbstractCommand
            ? $this->send($message, $messageDeliveryOptions)
            /** @var AbstractEvent $message */
            : $this->publish($message, $messageDeliveryOptions);
    }

    /**
     * @inheritdoc
     *
     * @throws \Desperado\Domain\MessageSerializer\Exceptions\MessageSerializationFailException
     * @throws OutboundContextNotAppliedException
     */
    final public function publish(AbstractEvent $event, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->guardOutboundContext();

        $context = $this->getOutboundMessageContext();

        if(null !== $context)
        {
            $context->publish($event, $messageDeliveryOptions);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws \Desperado\Domain\MessageSerializer\Exceptions\MessageSerializationFailException
     * @throws OutboundContextNotAppliedException
     */
    final public function send(AbstractCommand $command, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->guardOutboundContext();

        $context = $this->getOutboundMessageContext();

        if(null !== $context)
        {
            $context->send($command, $messageDeliveryOptions);
        }
    }

    /**
     * Log message in execution context
     *
     * @todo: fix me
     *
     * @param string $logMessage
     * @param string $level
     * @param array  $extra
     *
     * @return void
     */
    final public function logContextMessage(
        string $logMessage,
        string $level = LogLevel::INFO,
        array $extra = []
    ): void
    {
        $this->getLogger('execution')->log($level, $logMessage, $extra);
    }

    /**
     * Log Throwable in execution context
     *
     * @param \Throwable $throwable
     * @param string     $level
     * @param array      $extra
     *
     * @return void
     */
    public function logContextThrowable(
        \Throwable $throwable,
        string $level = LogLevel::ERROR,
        array $extra = []
    ): void
    {
        $this->logContextMessage(ThrowableFormatter::toString($throwable), $level, $extra);
    }

    /**
     * Get logger callable for execution context
     *
     * @param string $level
     *
     * @return callable
     */
    public function getContextThrowableCallableLogger(string $level = LogLevel::ERROR): callable
    {
        return function(\Throwable $throwable) use ($level)
        {
            $this->logContextMessage(ThrowableFormatter::toString($throwable), $level);
        };
    }

    /**
     * Get logger instance
     *
     * @param string $channelName
     *
     * @return LoggerInterface
     */
    abstract protected function getLogger(string $channelName): LoggerInterface;

    /**
     * Get scheduler provider
     *
     * @return SchedulerProvider
     */
    final protected function getSchedulerProvider(): SchedulerProvider
    {
        return $this->schedulerProvider;
    }

    /**
     * @return void
     *
     * @throws OutboundContextNotAppliedException
     */
    private function guardOutboundContext(): void
    {
        if(null === $this->getOutboundMessageContext())
        {
            throw new OutboundContextNotAppliedException();
        }
    }
}
