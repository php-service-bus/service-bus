<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests;

use Amp\Promise;
use Amp\Success;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class TestContext implements ServiceBusContext
{
    /**
     * @var object[]
     */
    public $messages = [];

    /** @var TestHandler */
    public $testLogHandler;

    /** @var LoggerInterface */
    private $logger;

    /** @var array */
    private $violations = [];

    /** @var bool */
    private $isValid = true;

    public function __construct()
    {
        $this->testLogHandler = new TestHandler();
        $this->logger         = new Logger(
            __CLASS__,
            [$this->testLogHandler]
        );
    }

    public function testLogHandler(): TestHandler
    {
        return $this->testLogHandler;
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
        $this->messages[\get_class($message)] = $message;

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
        return $this->isValid;
    }

    /**
     * {@inheritdoc}
     */
    public function violations(): array
    {
        return $this->violations;
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

    /** @noinspection PhpUnusedPrivateMethodInspection */
    private function validationFailed(array $violations): void
    {
        $this->isValid    = false;
        $this->violations = $violations;
    }
}
