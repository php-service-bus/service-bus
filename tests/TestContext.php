<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests;

use Amp\Promise;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use ServiceBus\Common\Context\ContextLogger;
use ServiceBus\Common\Context\DefaultContextLogger;
use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Common\Context\OutcomeMessageMetadata;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Context\ValidationViolations;
use ServiceBus\Common\Endpoint\DeliveryOptions;
use ServiceBus\EntryPoint\ReceivedMessageMetadata;
use function Amp\call;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class TestContext implements ServiceBusContext
{
    /**
     * @var object
     */
    private $incomeMessage;

    /**
     * @var object[]
     */
    public $messages = [];

    /**
     * @var TestHandler
     */
    public $testLogHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ValidationViolations|null
     */
    private $violations;

    public function __construct(object $incomeMessage)
    {
        $this->incomeMessage  = $incomeMessage;
        $this->testLogHandler = new TestHandler();
        $this->logger         = new Logger(
            __CLASS__,
            [$this->testLogHandler]
        );
    }

    public function violations(): ?ValidationViolations
    {
        return $this->violations;
    }

    public function delivery(
        object $message,
        ?DeliveryOptions $deliveryOptions = null,
        ?OutcomeMessageMetadata $withMetadata = null
    ): Promise {
        return call(
            function () use ($message)
            {
                $this->messages[] = $message;
            }
        );
    }

    public function deliveryBulk(
        array $messages,
        ?DeliveryOptions $deliveryOptions = null,
        ?OutcomeMessageMetadata $withMetadata = null
    ): Promise {
        return call(
            function () use ($messages)
            {
                foreach ($messages as $message)
                {
                    $this->messages[] = $message;
                }
            }
        );
    }

    public function logger(): ContextLogger
    {
        return new DefaultContextLogger($this->logger, $this->incomeMessage, $this->metadata());
    }

    public function headers(): array
    {
        return [];
    }

    public function metadata(): IncomingMessageMetadata
    {
        return new ReceivedMessageMetadata(uuid(), uuid(), []);
    }

    /**
     * Message failed validation
     * Called by infrastructure components.
     *
     * @codeCoverageIgnore
     * @noinspection PhpUnusedPrivateMethodInspection
     *
     * @see          MessageValidationExecutor
     */
    private function validationFailed(ValidationViolations $validationViolations): void
    {
        $this->violations = $validationViolations;
    }
}
