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

namespace Desperado\ServiceBus\Endpoint;

/**
 * Sent message options
 */
final class DeliveryOptions
{
    /**
     * Headers bag
     *
     * @var array<string, string|int|float>
     */
    private $headers;

    /**
     * The message must be stored in the broker
     *
     * @var bool
     */
    private $isPersistent = true;

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
     * Trace operation id
     *
     * @var string|null
     */
    private $traceId;

    /**
     * @param array<string, string|int|float> $headers
     */
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
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
     * Receive message headers
     *
     * @return array<string, string|int|float>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Receive trace id
     *
     * @return string|null
     */
    public function traceId(): ?string
    {
        return $this->traceId;
    }

    /**
     * Apply trace id
     * By default, the incoming message ID will be assigned
     *
     * @param string $id
     *
     * @return void
     */
    public function withCustomTraceId(string $id): void
    {
        $this->traceId = $id;
    }

    /**
     * Add headers to sent
     *
     * @param array<string, string> $headers
     *
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = \array_merge($this->headers, $headers);

        return $this;
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
}
