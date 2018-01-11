<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\MessageProcessor;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\Domain\ThrowableFormatter;
use Desperado\ServiceBus\Application\Exceptions\OutboundContextNotAppliedException;
use Desperado\ServiceBus\Extensions\Logger\ServiceBusLogger;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContext;
use Desperado\Domain\Transport\Message\MessageDeliveryOptions;
use Psr\Log\LogLevel;

/**
 * Base class of the context of message processing
 */
abstract class AbstractExecutionContext implements ExecutionContextInterface
{
    /**
     * Get outbound context
     *
     * @return OutboundMessageContext|null
     */
    abstract public function getOutboundMessageContext(): ?OutboundMessageContext;

    /**
     * @inheritdoc
     *
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
     * @throws OutboundContextNotAppliedException
     */
    final public function publish(AbstractEvent $event, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->guardOutboundContext();

        $this
            ->getOutboundMessageContext()
            ->publish($event, $messageDeliveryOptions);

    }

    /**
     * @inheritdoc
     *
     * @throws OutboundContextNotAppliedException
     */
    final public function send(AbstractCommand $command, MessageDeliveryOptions $messageDeliveryOptions): void
    {
        $this->guardOutboundContext();

        $this
            ->getOutboundMessageContext()
            ->send($command, $messageDeliveryOptions);
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
        ServiceBusLogger::log('execution', $logMessage, $level, $extra);
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
