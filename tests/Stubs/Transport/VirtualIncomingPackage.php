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

use Amp\ByteStream\InMemoryStream;
use Amp\ByteStream\InputStream;
use Amp\Promise;
use Amp\Success;
use function Desperado\ServiceBus\Common\uuid;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Desperado\ServiceBus\OutboundMessage\Destination;

/**
 *
 */
final class VirtualIncomingPackage implements IncomingPackage
{
    /**
     * @var string
     */
    private $payload;

    /**
     * @var array
     */
    private $headers;

    /**
     * @param string $payload
     * @param array  $headers
     */
    public function __construct(string $payload, array $headers)
    {
        $this->payload = $payload;
        $this->headers = $headers;
    }

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
        return \microtime(true);
    }

    /**
     * @inheritDoc
     */
    public function origin(): Destination
    {
        return new Destination('virtual', 'virtual');
    }

    /**
     * @inheritDoc
     */
    public function payload(): InputStream
    {
        return new InMemoryStream($this->payload);
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
}
