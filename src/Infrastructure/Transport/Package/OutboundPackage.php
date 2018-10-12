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

namespace Desperado\ServiceBus\Infrastructure\Transport\Package;

use Amp\ByteStream\InputStream;
use Desperado\ServiceBus\OutboundMessage\Destination;

/**
 * Outbound package
 */
final class OutboundPackage
{
    /**
     * Message body
     *
     * @var InputStream
     */
    private $payload;

    /**
     * Message headers
     *
     * @var array<string, string>
     */
    private $headers;

    /**
     * Message destination
     *
     * @var Destination
     */
    private $destination;

    /**
     * The message must be stored in the broker
     *
     * @var bool
     */
    private $isPersistent = false;

    /**
     * The message must be sent to the existing recipient
     *
     * @var bool
     */
    private $isMandatory = false;

    /**
     * The message will be sent with the highest priority
     *
     * @var bool
     */
    private $isImmediate = false;

    /**
     * The message will be marked expired after N milliseconds
     *
     * @var int|null
     */
    private $expiredAfter;

    /**
     * @param InputStream $payload
     * @param array       $headers
     * @param Destination $destination
     */
    public function __construct(InputStream $payload, array $headers, Destination $destination)
    {
        $this->payload     = $payload;
        $this->headers     = $headers;
        $this->destination = $destination;
    }

    /**
     * @return bool
     */
    public function isPersistent(): bool
    {
        return $this->isPersistent;
    }

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return $this->isMandatory;
    }

    /**
     * @return bool
     */
    public function isImmediate(): bool
    {
        return $this->isImmediate;
    }

    /**
     * @return int|null
     */
    public function expiredAfter(): ?int
    {
        return $this->expiredAfter;
    }

    /**
     * When publishing a message, the message must be routed to a valid queue. If it is not, an error will be returned
     *
     * @return $this
     */
    public function makeMandatory(): self
    {
        $this->isMandatory = true;

        return $this;
    }

    /**
     * When publishing a message, mark this message for immediate processing by the broker (High priority message)
     *
     * @return $this
     */
    public function makeImmediate(): self
    {
        $this->isImmediate = true;

        return $this;
    }

    /**
     * Setup message TTL
     *
     * @param int $milliseconds
     *
     * @return $this
     */
    public function makeExpiredAfter(int $milliseconds): self
    {
        $this->expiredAfter = $milliseconds;

        return $this;
    }

    /**
     * Save message in broker
     *
     * @return $this
     */
    public function makePersistent(): self
    {
        $this->isPersistent = true;

        return $this;
    }

    /**
     * To whom the message will be sent
     *
     * @return Destination
     */
    public function destination(): Destination
    {
        return $this->destination;
    }

    /**
     * Receive message body
     *
     * @return InputStream
     */
    public function payload(): InputStream
    {
        return $this->payload;
    }

    /**
     * Receive message headers
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
