<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Stubs\Context;

use Amp\Promise;
use Amp\Success;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\Common\Messages\Message;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class TestContext implements ServiceBusContext
{
    public $messages = [];

    /**
     * @var TestHandler
     */
    public $testLogHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct()
    {
        $this->testLogHandler = new TestHandler();
        $this->logger         = new Logger(
            __CLASS__,
            [$this->testLogHandler]
        );
    }

    /**
     * @return TestHandler
     */
    public function testLogHandler(): TestHandler
    {
        return $this->testLogHandler;
    }

    /**
     * @inheritdoc
     */
    public function delivery(Message $message, ?DeliveryOptions $deliveryOptions = null): Promise
    {
        $this->messages[] = $message;

        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function logContextMessage(string $logMessage, array $extra = [], string $level = LogLevel::INFO): void
    {
        $this->logger->log($level, $logMessage, $extra);
    }

    /**
     * @inheritDoc
     */
    public function logContextThrowable(\Throwable $throwable, string $level = LogLevel::ERROR, array $extra = []): void
    {
        $this->logContextMessage($throwable->getMessage());
    }

    /**
     * @inheritDoc
     */
    public function isValid(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function violations(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function operationId(): string
    {
        return uuid();
    }

    /**
     * @inheritDoc
     */
    public function traceId(): string
    {
        return uuid();
    }


}
