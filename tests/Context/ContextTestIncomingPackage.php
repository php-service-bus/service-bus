<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Context;

use Amp\Promise;
use Amp\Success;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 *
 */
final class ContextTestIncomingPackage implements IncomingPackage
{
    /** @var string */
    private $id;

    /** @var string */
    private $traceId;

    /** @var array */
    private $headers;

    /** @var string */
    private $payload;

    public function __construct(string $id, string $traceId, string $payload = '', array $headers = [])
    {
        $this->id      = $id;
        $this->traceId = $traceId;
        $this->payload = $payload;
        $this->headers = $headers;
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
        return new ContextTestDestination();
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