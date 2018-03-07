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

use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContextInterface;
use Desperado\ServiceBus\Transport\Message\MessageDeliveryOptions;
use Psr\Log\LogLevel;

/**
 * Interface of the context of message processing
 */
interface ExecutionContextInterface
{
    /**
     * Apply a context to send the message to the transport layer
     * Must return a NEW instance of the class (immutable object)
     *
     * @param OutboundMessageContextInterface $outboundMessageContext
     *
     * @return $this
     */
    public function applyOutboundMessageContext(OutboundMessageContextInterface $outboundMessageContext);

    /**
     * Get outbound context
     *
     * @return OutboundMessageContextInterface|null
     */
    public function getOutboundMessageContext(): ?OutboundMessageContextInterface;

    /**
     * Send command/publish message
     *
     * @param AbstractMessage             $message
     * @param MessageDeliveryOptions|null $messageDeliveryOptions
     *
     * @return void
     */
    public function delivery(AbstractMessage $message, MessageDeliveryOptions $messageDeliveryOptions = null): void;

    /**
     * Publish event
     *
     * @param AbstractEvent          $event
     * @param MessageDeliveryOptions $messageDeliveryOptions
     *
     * @return void
     */
    public function publish(AbstractEvent $event, MessageDeliveryOptions $messageDeliveryOptions): void;

    /**
     * Send command
     *
     * @param AbstractCommand        $command
     * @param MessageDeliveryOptions $messageDeliveryOptions
     *
     * @return void
     */
    public function send(AbstractCommand $command, MessageDeliveryOptions $messageDeliveryOptions): void;

    /**
     *  Log message in execution context
     *
     * @param string $logMessage
     * @param string $level
     * @param array  $extra
     *
     * @return void
     */
    public function logContextMessage(
        string $logMessage,
        string $level = LogLevel::INFO,
        array $extra = []
    ): void;
}
