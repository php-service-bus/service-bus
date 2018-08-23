<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Common\Contract\Messages\Command;
use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Common\Contract\Messages\Message;
use Desperado\ServiceBus\Common\ExecutionContext\LoggingInContext;
use Desperado\ServiceBus\Common\ExecutionContext\MessageDeliveryContext;
use Desperado\ServiceBus\Transport\IncomingEnvelope;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 *
 */
final class KernelContext implements MessageDeliveryContext, LoggingInContext
{
    /**
     * Send message handler
     *
     * @see ServiceBusKernel::createMessageSender()
     *
     * @var \Generator
     */
    private $messageSender;

    /**
     * @var IncomingEnvelope
     */
    private $incomingEnvelope;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IncomingEnvelope $incomingEnvelope
     * @param \Generator       $messageSender
     * @param LoggerInterface  $logger
     */
    public function __construct(IncomingEnvelope $incomingEnvelope, \Generator $messageSender, LoggerInterface $logger)
    {
        $this->incomingEnvelope = $incomingEnvelope;
        $this->messageSender    = $messageSender;
        $this->logger           = $logger;
    }

    /**
     * @inheritdoc
     */
    public function delivery(Message ...$messages): Promise
    {
        $messageSender    = $this->messageSender;
        $incomingEnvelope = $this->incomingEnvelope;

        /** @psalm-suppress InvalidArgument */
        return call(
            static function(array $messages) use ($messageSender, $incomingEnvelope): void
            {
                foreach($messages as $message)
                {
                    $messageSender->send([$message, $incomingEnvelope, []]);
                }
            },
            $messages
        );
    }

    /**
     * @inheritDoc
     */
    public function send(Command $command, array $headers = []): Promise
    {
        $messageSender    = $this->messageSender;
        $incomingEnvelope = $this->incomingEnvelope;

        /** @psalm-suppress InvalidArgument */
        return call(
            static function(Command $command, array $headers) use ($messageSender, $incomingEnvelope): void
            {
                $messageSender->send([$command, $incomingEnvelope, $headers]);
            },
            $command,
            $headers
        );
    }

    /**
     * @inheritDoc
     */
    public function publish(Event $event, array $headers = []): Promise
    {
        $messageSender    = $this->messageSender;
        $incomingEnvelope = $this->incomingEnvelope;

        /** @psalm-suppress InvalidArgument */
        return call(
            static function(Event $event, array $headers) use ($messageSender, $incomingEnvelope): void
            {
                $messageSender->send([$event, $incomingEnvelope, $headers]);
            },
            $event,
            $headers
        );
    }

    /**
     * Receive incoming envelope
     *
     * @return IncomingEnvelope
     */
    public function incomingEnvelope(): IncomingEnvelope
    {
        return $this->incomingEnvelope;
    }

    /**
     * @inheritdoc
     */
    public function logContextMessage(
        string $logMessage,
        array $extra = [],
        string $level = LogLevel::INFO
    ): void
    {
        $extra = \array_merge_recursive($extra, [
                'operationId' => $this->incomingEnvelope->operationId()
            ]
        );

        $this->logger->log($level, $logMessage, $extra);
    }

    /**
     * @inheritdoc
     */
    public function logContextThrowable(
        \Throwable $throwable,
        string $level = LogLevel::ERROR,
        array $extra = []
    ): void
    {
        $this->logContextMessage(
            $throwable->getMessage(),
            \array_merge_recursive(
                $extra,
                ['throwable' => $throwable]
            ),
            $level
        );
    }
}
