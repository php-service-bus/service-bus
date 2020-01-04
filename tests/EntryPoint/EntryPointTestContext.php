<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\EntryPoint;

use Amp\Promise;
use Amp\Success;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class EntryPointTestContext implements ServiceBusContext
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function headers(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function delivery(object $message, ?DeliveryOptions $deliveryOptions = null): Promise
    {
        return new Success();
    }

    /**
     * {@inheritdoc}
     */
    public function return(int $secondsDelay = 3): Promise
    {
        return new Success();
    }

    /**
     * {@inheritdoc}
     */
    public function logContextMessage(string $logMessage, array $extra = [], string $level = LogLevel::INFO): void
    {
        $this->logger->log($level, $logMessage, $extra);
    }

    /**
     * {@inheritdoc}
     */
    public function logContextThrowable(\Throwable $throwable, array $extra = [], string $level = LogLevel::ERROR): void
    {
        $this->logContextMessage($throwable->getMessage(), $extra, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function violations(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function operationId(): string
    {
        return uuid();
    }

    /**
     * {@inheritdoc}
     */
    public function traceId(): string
    {
        return uuid();
    }
}
