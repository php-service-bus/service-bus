<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Saga;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\Domain\Transport\Context\OutboundMessageContextInterface;
use Desperado\Domain\Transport\Message\MessageDeliveryOptions;
use Psr\Log\LogLevel;

/**
 *
 */
class LocalDeliveryContext implements ExecutionContextInterface
{
    /**
     * @var AbstractEvent[]
     */
    private $publishedEvents = [];

    /**
     * @var AbstractCommand[]
     */
    private $publishedCommands = [];

    /**
     * @return AbstractEvent[]
     */
    public function getPublishedEvents(): array
    {
        return $this->publishedEvents;
    }

    /**
     * @return AbstractCommand[]
     */
    public function getPublishedCommands(): array
    {
        return $this->publishedCommands;
    }

    /**
     * @inheritdoc
     */
    public function applyOutboundMessageContext(OutboundMessageContextInterface $outboundMessageContext)
    {

    }

    /**
     * @inheritdoc
     */
    public function delivery(AbstractMessage $message, MessageDeliveryOptions $deliveryOptions = null): void
    {
        $message instanceof AbstractEvent
            ? $this->publish($message, $deliveryOptions ?? MessageDeliveryOptions::create())
            /** @var AbstractCommand $message */
            : $this->send($message, $deliveryOptions ?? MessageDeliveryOptions::create());
    }

    /**
     * @inheritdoc
     */
    public function send(AbstractCommand $command, MessageDeliveryOptions $deliveryOptions): void
    {
        $this->publishedCommands[] = $command;

        unset($deliveryOptions);
    }

    /**
     * @inheritdoc
     */
    public function publish(AbstractEvent $event, MessageDeliveryOptions $deliveryOptions): void
    {
        $this->publishedEvents[] = $event;

        unset($deliveryOptions);
    }

    /**
     * @inheritdoc
     *
     * @throws \LogicException
     */
    public function getOutboundMessageContext(): ?OutboundMessageContextInterface
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @inheritdoc
     *
     * @throws \LogicException
     */
    public function logContextMessage(
        string $logMessage,
        string $level = LogLevel::INFO,
        array $extra = []
    ): void
    {

    }
}
