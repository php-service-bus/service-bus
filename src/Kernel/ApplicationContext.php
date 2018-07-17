<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Kernel;

use function Amp\call;
use Amp\Promise;
use Desperado\Contracts\Common\Message;
use Desperado\Sagas\SagaContext;
use Desperado\ServiceBus\Transport\IncomingEnvelope;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Application-level context
 */
final class ApplicationContext implements SagaContext
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
    public function delivery(Message ...$message): Promise
    {
        $incomingEnvelope = $this->incomingEnvelope;
        $messageSender    = $this->messageSender;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(array $messages) use ($messageSender, $incomingEnvelope): void
            {
                foreach($messages as $message)
                {
                    /**
                     * @see ServiceBusKernel::createMessageSender()
                     *
                     * @var Message $message
                     */
                    $messageSender->send([$message, $incomingEnvelope]);
                }
            },
            $message
        );
    }

    /**
     * Log message with context details
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
    ): void
    {
        $extra = \array_merge_recursive($extra, [
                'operationId' => $this->incomingEnvelope->operationId()
            ]
        );

        $this->logger->log($level, $logMessage, $extra);
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
        $this->logContextMessage(
            $throwable->getMessage(),
            $level,
            \array_merge_recursive(
                $extra,
                ['throwable' => $throwable]
            )
        );
    }

    /**
     * Receive operation id
     *
     * @return string
     */
    public function operationId(): string
    {
        return $this->incomingEnvelope->operationId();
    }
}
