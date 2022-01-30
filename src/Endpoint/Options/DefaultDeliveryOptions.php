<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

namespace ServiceBus\Endpoint\Options;

use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 * Sent message options.
 *
 * @psalm-immutable
 */
final class DefaultDeliveryOptions implements DeliveryOptions
{
    /**
     * Headers bag.
     *
     * @psalm-readonly
     * @psalm-var array<non-empty-string, int|float|string|null>
     *
     * @var array
     */
    public $headers;

    /**
     * The message must be stored in the broker.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $isPersistent = true;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue. If this flag is set, the
     * server will return an unroutable message with a Return method. If this flag is false, the server silently drops
     * the message.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $isMandatory = true;

    /**
     * This flag tells the server how to react if the message cannot be routed to a queue consumer immediately. If this
     * flag is set, the server will return an undeliverable message with a Return method. If this flag is false, the
     * server will queue the message, but with no guarantee that it will ever be consumed.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $isImmediate = false;

    /**
     * The message will be marked expired after N milliseconds.
     *
     * @psalm-readonly
     *
     * @var int|null
     */
    public $expiredAfter;

    public static function create(): self
    {
        return new self();
    }

    /**
     * @psalm-param array<non-empty-string, int|float|string|null> $headers
     */
    public static function nonPersistent(array $headers = []): self
    {
        return new self(
            headers: $headers,
            isPersistent: false,
        );
    }

    public function withHeader(string $key, int|float|string|null $value): self
    {
        $headers = $this->headers;
        $headers[$key] = $value;

        return new self(
            headers: $headers,
            isPersistent: $this->isPersistent(),
            isMandatory: $this->isMandatory,
            isImmediate: $this->isImmediate,
            expiredAfter: $this->expiredAfter
        );
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function isPersistent(): bool
    {
        return $this->isPersistent;
    }

    public function isHighestPriority(): bool
    {
        return $this->isImmediate;
    }

    public function expirationAfter(): ?int
    {
        return $this->expiredAfter;
    }

    /**
     * @psalm-param array<non-empty-string, int|float|string|null> $headers
     */
    private function __construct(
        array $headers = [],
        bool $isPersistent = true,
        bool $isMandatory = true,
        bool $isImmediate = false,
        ?int $expiredAfter = null
    ) {
        $this->headers      = $headers;
        $this->isPersistent = $isPersistent;
        $this->isMandatory  = $isMandatory;
        $this->isImmediate  = $isImmediate;
        $this->expiredAfter = $expiredAfter;
    }
}
