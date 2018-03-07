<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Transport\Context;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\ServiceBus\Transport\Message\MessageDeliveryOptions;


/**
 * Outbound message context
 */
interface OutboundMessageContextInterface
{
    /**
     * @param IncomingMessageContextInterface $incomingMessageContext
     * @param MessageSerializerInterface      $messageSerializer
     *
     * @return OutboundMessageContextInterface
     */
    public static function fromIncoming(
        IncomingMessageContextInterface $incomingMessageContext,
        MessageSerializerInterface $messageSerializer
    );

    /**
     * Publish event
     *
     * @param AbstractEvent          $event
     * @param MessageDeliveryOptions $messageDeliveryOptions
     *
     * @return void
     *
     * @throws \Desperado\Domain\MessageSerializer\Exceptions\MessageSerializationFailException
     */
    public function publish(AbstractEvent $event, MessageDeliveryOptions $messageDeliveryOptions): void;

    /**
     * Send command
     *
     * @param AbstractCommand       $command
     * @param MessageDeliveryOptions $messageDeliveryOptions
     *
     * @return void
     *
     * @throws \Desperado\Domain\MessageSerializer\Exceptions\MessageSerializationFailException
     */
    public function send(AbstractCommand $command, MessageDeliveryOptions $messageDeliveryOptions): void;

    /**
     * Get messages to be sent to the transport bus
     *
     * @return \SplObjectStorage
     *
     * @throws \Desperado\Domain\MessageSerializer\Exceptions\MessageSerializationFailException
     */
    public function getToPublishMessages(): \SplObjectStorage;
}
