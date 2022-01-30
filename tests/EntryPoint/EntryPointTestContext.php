<?php

/** @noinspection PhpUnusedParameterInspection */

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\EntryPoint;

use Amp\Promise;
use Amp\Success;
use Psr\Log\LoggerInterface;
use ServiceBus\Common\Context\ContextLogger;
use ServiceBus\Common\Context\DefaultContextLogger;
use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Common\Context\OutcomeMessageMetadata;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Common\Context\ValidationViolations;
use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 *
 */
final class EntryPointTestContext implements ServiceBusContext
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

    public function violations(): ?ValidationViolations
    {
        return null;
    }

    public function delivery(
        object $message,
        ?DeliveryOptions $deliveryOptions = null,
        ?OutcomeMessageMetadata $withMetadata = null
    ): Promise {
        return new Success();
    }

    public function deliveryBulk(
        array $messages,
        ?DeliveryOptions $deliveryOptions = null,
        ?OutcomeMessageMetadata $withMetadata = null
    ): Promise {
        return new Success();
    }

    public function return(int $secondsDelay = 3, ?OutcomeMessageMetadata $withMetadata = null): Promise
    {
        return new Success();
    }

    public function logger(): ContextLogger
    {
        return new DefaultContextLogger($this->logger, $this->message, $this->metadata());
    }

    public function headers(): array
    {
        return [];
    }

    public function metadata(): IncomingMessageMetadata
    {
        return $this->metadata;
    }
}
