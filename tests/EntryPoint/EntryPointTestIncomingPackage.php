<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\EntryPoint;

use Amp\Promise;
use Amp\Success;
use ServiceBus\Metadata\ServiceBusMetadata;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class EntryPointTestIncomingPackage implements IncomingPackage
{
    /** @var string */
    private $id;

    /** @var array */
    private $headers;

    /** @var string */
    private $payload;

    public function __construct(
        string $payload = '',
        array $headers = [],
        ?string $messageId = null
    )
    {
        $this->payload = $payload;
        $this->headers = $headers;
        $this->id      = $messageId ?? uuid();
    }

    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function origin(): DeliveryDestination
    {
        return new class() implements DeliveryDestination {
        };
    }

    /**
     * @inheritDoc
     */
    public function payload(): string
    {
        return $this->payload;
    }

    /**
     * @inheritDoc
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function ack(): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function nack(bool $requeue, ?string $withReason = null): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function reject(bool $requeue, ?string $withReason = null): Promise
    {
        return new Success();
    }

    /**
     * @inheritDoc
     */
    public function traceId()
    {
        return $this->traceId;
    }
}
