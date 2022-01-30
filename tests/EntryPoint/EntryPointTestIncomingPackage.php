<?php

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
use ServiceBus\Transport\Common\DeliveryDestination;
use ServiceBus\Transport\Common\Package\IncomingPackage;
use function ServiceBus\Common\uuid;

/**
 *
 */
final class EntryPointTestIncomingPackage implements IncomingPackage
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

    public function __construct(
        string $payload,
        array $headers,
        string $messageId
    ) {
        $this->payload = $payload;
        $this->headers = $headers;
        $this->id      = $messageId;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function traceId(): string
    {
        return uuid();
    }

    public function origin(): DeliveryDestination
    {
        return new class () implements DeliveryDestination
        {
        };
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
