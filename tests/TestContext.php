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
    private object $incomeMessage;

    private ReceivedMessageMetadata $receivedMessageMetadata;

    /**
     * @var array<string, object>
     */
    public array $messages = [];

    /**
     * @var array<string, OutcomeMessageMetadata>
     */
    public array $withMetadata = [];

    public TestHandler $testLogHandler;

    private LoggerInterface $logger;

    private ?ValidationViolations $violations = null;

    public function __construct(object $incomeMessage)
    {
        $this->incomeMessage  = $incomeMessage;
        $this->receivedMessageMetadata = new ReceivedMessageMetadata(uuid(), uuid(), []);
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
            function () use ($message, $withMetadata) {
                $id = \spl_object_hash($message);

                $this->messages[$id] = $message;
                if ($withMetadata !== null) {
                    $this->withMetadata[$id] = $withMetadata;
                }
            }
        );
    }

    public function deliveryBulk(
        array $messages,
        ?DeliveryOptions $deliveryOptions = null,
        ?OutcomeMessageMetadata $withMetadata = null
    ): Promise {
        return call(
            function () use ($messages, $withMetadata) {
                foreach ($messages as $message) {
                    $id = \spl_object_hash($message);

                    $this->messages[$id] = $message;
                    if ($withMetadata !== null) {
                        $this->withMetadata[$id] = $withMetadata;
                    }
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
        return $this->receivedMessageMetadata;
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
