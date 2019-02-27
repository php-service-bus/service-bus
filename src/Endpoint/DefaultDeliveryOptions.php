<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint;

use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 * Sent message options.
 *
 * @property-read array           $headers
 * @property-read bool            $isPersistent
 * @property-read bool            $isMandatory
 * @property-read bool            $isImmediate
 * @property-read int|null        $expiredAfter
 * @property-read int|string|null $traceId
 */
final class DefaultDeliveryOptions implements DeliveryOptions
{
    /**
     * Headers bag.
     *
     * @psalm-var array<string, string|int|float>
     *
     * @var array<string, float|int|string>
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
    public $expiredAfter;

    /**
     * Trace operation id.
     *
     * @var int|string|null
     */
    public $traceId;

    /**
     * {@inheritdoc}
     */
    public static function create(): DeliveryOptions
    {
        return new self();
    }

    /**
     * @psalm-param array<string, string|int|float> $headers
     *
     * @param array $headers
     *
     * @return self
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
     *
     * @psalm-suppress MixedTypeCoercion
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
     * @param array           $headers
     * @param bool            $isPersistent
     * @param bool            $isMandatory
     * @param bool            $isImmediate
     * @param int|null        $expiredAfter
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
