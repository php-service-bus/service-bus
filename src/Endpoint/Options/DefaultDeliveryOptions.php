<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint\Options;

use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 * Sent message options.
 */
final class DefaultDeliveryOptions implements DeliveryOptions
{
    /**
     * Headers bag.
     *
     * @psalm-var array<string, string|int|float>
     *
     * @var array
     */
    public $headers;

    /**
     * The message must be stored in the broker.
     *
     * @var bool
     */
    public $isPersistent = true;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue. If this flag is set, the
     * server will return an unroutable message with a Return method. If this flag is false, the server silently drops
     * the message.
     *
     * @var bool
     */
    public $isMandatory = true;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue consumer immediately. If this
     * flag is set, the server will return an undeliverable message with a Return method. If this flag is false, the
     * server will queue the message, but with no guarantee that it will ever be consumed.
     *
     * @var bool
     */
    public $isImmediate = false;

    /**
     * The message will be marked expired after N milliseconds.
     *
     * @var int|null
     */
    public $expiredAfter = null;

    /**
     * Trace operation id.
     *
     * @var int|string|null
     */
    public $traceId = null;

    /**
     * {@inheritdoc}
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @psalm-param array<string, string|int|float> $headers
     */
    public static function nonPersistent(array $headers = []): self
    {
        return new self($headers, false);
    }

    /**
     * {@inheritdoc}
     */
    public function withTraceId($traceId): void
    {
        $this->traceId = $traceId;
    }

    /**
     * {@inheritdoc}
     */
    public function withHeader(string $key, $value): void
    {
        /** @psalm-suppress MixedTypeCoercion */
        $this->headers[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function traceId()
    {
        return $this->traceId;
    }

    /**
     * {@inheritdoc}
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function isPersistent(): bool
    {
        return $this->isPersistent;
    }

    /**
     * {@inheritdoc}
     */
    public function isHighestPriority(): bool
    {
        return $this->isImmediate;
    }

    /**
     * {@inheritdoc}
     */
    public function expirationAfter(): ?int
    {
        return $this->expiredAfter;
    }

    /**
     * @psalm-param array<string, string|int|float> $headers
     *
     * @param int|string|null $traceId
     */
    private function __construct(
        array $headers = [],
        bool $isPersistent = true,
        bool $isMandatory = true,
        bool $isImmediate = false,
        ?int $expiredAfter = null,
        $traceId = null
    ) {
        $this->headers      = $headers;
        $this->isPersistent = $isPersistent;
        $this->isMandatory  = $isMandatory;
        $this->isImmediate  = $isImmediate;
        $this->expiredAfter = $expiredAfter;
        $this->traceId      = $traceId;
    }
}
