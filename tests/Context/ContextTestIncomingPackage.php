<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\Context;

use Amp\Promise;
use Amp\Success;
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class ContextTestIncomingPackage implements IncomingPackage
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string
     */
    private $payload;

    public function __construct(string $id, array $headers, string $payload)
    {
        $this->id      = $id;
        $this->headers = $headers;
        $this->payload = $payload;
    }

    public function traceId(): string
    {
        return uuid();
    }

    public function id(): string
    {
        return $this->id;
    }

    public function origin(): DeliveryDestination
    {
        return new ContextTestDestination();
    }

    public function payload(): string
    {
        return $this->payload;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function ack(): Promise
    {
        return new Success();
    }

    public function nack(bool $requeue, ?string $withReason = null): Promise
    {
        return new Success();
    }

    public function reject(bool $requeue, ?string $withReason = null): Promise
    {
        return new Success();
    }
}
