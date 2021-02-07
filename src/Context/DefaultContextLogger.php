<?php
/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Context;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ContextLogger;
use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Metadata\ServiceBusMetadata;
use function ServiceBus\Common\throwableDetails;
use function ServiceBus\Common\throwableMessage;

/**
 *
 */
final class DefaultContextLogger implements ContextLogger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var object
     */
    private $message;

    /**
     * @var IncomingMessageMetadata
     */
    private $metadata;

    public function __construct(LoggerInterface $logger, object $message, IncomingMessageMetadata $metadata)
    {
        $this->logger   = $logger;
        $this->message  = $message;
        $this->metadata = $metadata;
    }

    public function throwable(\Throwable $throwable, array $extra = [], string $level = LogLevel::ERROR): void
    {
        $this->log(
            level: $level,
            message: throwableMessage($throwable),
            context: \array_merge($extra, throwableDetails($throwable))
        );
    }

    public function emergency($message, array $context = []): void
    {
        $this->log(
            level: LogLevel::EMERGENCY,
            message: $message,
            context: $context
        );
    }

    public function alert($message, array $context = []): void
    {
        $this->log(
            level: LogLevel::ALERT,
            message: $message,
            context: $context
        );
    }

    public function critical($message, array $context = []): void
    {
        $this->log(
            level: LogLevel::CRITICAL,
            message: $message,
            context: $context
        );
    }

    public function error($message, array $context = []): void
    {
        $this->log(
            level: LogLevel::ERROR,
            message: $message,
            context: $context
        );
    }

    public function warning($message, array $context = []): void
    {
        $this->log(
            level: LogLevel::WARNING,
            message: $message,
            context: $context
        );
    }

    public function notice($message, array $context = []): void
    {
        $this->log(
            level: LogLevel::NOTICE,
            message: $message,
            context: $context
        );
    }

    public function info($message, array $context = []): void
    {
        $this->log(
            level: LogLevel::INFO,
            message: $message,
            context: $context
        );
    }

    public function debug($message, array $context = []): void
    {
        $this->log(
            level: LogLevel::DEBUG,
            message: $message,
            context: $context
        );
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logger->log(
            level: $level,
            message: $message,
            context: $this->collectExtraInformation($context)
        );
    }

    private function collectExtraInformation(array $extra): array
    {
        return \array_merge($extra, [
            'incomingMessage' => \get_class($this->message),
            'messageId'       => $this->metadata->messageId(),
            'traceId'         => $this->metadata->get(ServiceBusMetadata::SERVICE_BUS_TRACE_ID),
            'retries'         => (int) $this->metadata->get(
                key: ServiceBusMetadata::SERVICE_BUS_MESSAGE_RETRY_COUNT,
                default: 0
            )
        ]);
    }
}
