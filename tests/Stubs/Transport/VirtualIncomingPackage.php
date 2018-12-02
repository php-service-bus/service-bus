<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Stubs\Transport;

use Amp\Promise;
use Amp\Success;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Endpoint\TransportLevelDestination;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;

/**
 *
 */
final class VirtualIncomingPackage implements IncomingPackage
{
    /**
     * @var string
     */
    public $payload;

    /**
     * @var array
     */
    public $headers = [];

    /**
     * @inheritDoc
     */
    public function id(): string
    {
        return uuid();
    }

    /**
     * @inheritDoc
     */
    public function time(): float
    {
        return microtime();
    }

    /**
     * @inheritDoc
     */
    public function origin(): TransportLevelDestination
    {
        return new VirtualDestination();
    }

    /**
     * @inheritDoc
     */
    public function payload(): string
    {
        return new $this->payload;
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
    public function traceId(): string
    {
        return uuid();
    }
}
