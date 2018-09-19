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

use function Amp\asyncCall;
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
     * @var callable
     */
    private $messagePublisher;

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
     * @param callable         $messagePublisher function(Message $message, array $headers, IncomingEnvelope
     *                                           $incomingEnvelope): Promise {}
     * @param LoggerInterface  $logger
     */
    public function __construct(IncomingEnvelope $incomingEnvelope, callable $messagePublisher, LoggerInterface $logger)
    {
        $this->incomingEnvelope = $incomingEnvelope;
        $this->messagePublisher = $messagePublisher;
        $this->logger           = $logger;
    }

    /**
     * @inheritdoc
     */
    public function delivery(Message ...$messages): Promise
    {
        return $this->processSendMessages($messages, []);
    }

    /**
     * @inheritDoc
     */
    public function send(Command $command, array $headers = []): Promise
    {
        return $this->processSendMessages([$command], $headers);
    }

    /**
     * @inheritDoc
     */
    public function publish(Event $event, array $headers = []): Promise
    {
        return $this->processSendMessages([$event], $headers);
    }

    /**
     * Execute messages sent
     *
     * @param array $messages
     * @param array $headers
     *
     * @return Promise<null>
     */
    private function processSendMessages(array $messages, array $headers): Promise
    {
        $messagePublisher = $this->messagePublisher;
        $incomingEnvelope = $this->incomingEnvelope;

        /** @psalm-suppress InvalidArgument */
        return call(
            static function(array $messages, array $headers) use ($messagePublisher, $incomingEnvelope): void
            {
                foreach($messages as $message)
                {
                    asyncCall($messagePublisher, $message, $headers, $incomingEnvelope);
                }
            },
            $messages, $headers
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
        $extra = \array_merge_recursive($extra, ['operationId' => $this->incomingEnvelope->operationId()]);

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
        $extra = \array_merge_recursive(
            $extra, ['throwablePoint' => \sprintf('%s:%d', $throwable->getFile(), $throwable->getLine())]
        );

        $this->logContextMessage($throwable->getMessage(), $extra, $level);
    }
}
