<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint;

use ServiceBus\Common\Endpoint\DeliveryOptions;

/**
 * Sent message options
 *
 * @property-read array<string, string|int|float> $headers
 * @property-read bool                            $isPersistent
 * @property-read bool                            $isMandatory
 * @property-read bool                            $isImmediate
 * @property-read int|null                        $expiredAfter
 * @property-read string|int|null                 $traceId
 */
final class DefaultDeliveryOptions implements DeliveryOptions
{
    /**
     * Headers bag
     *
     * @var array<string, string|int|float>
     */
    public $headers;

    /**
     * The message must be stored in the broker
     *
     * @var bool
     */
    public $isPersistent = true;

    /**
     * The message must be sent to the existing recipient
     *
     * @var bool
     */
    public $isMandatory = false;

    /**
     * The message will be sent with the highest priority
     *
     * @var bool
     */
    public $isImmediate = false;

    /**
     * The message will be marked expired after N milliseconds
     *
     * @var int|null
     */
    public $expiredAfter;

    /**
     * Trace operation id
     *
     * @var string|int|null
     */
    public $traceId;

    /**
     * @return self
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @param array<string, string|int|float> $headers
     *
     * @return self
     */
    public static function nonPersistent(array $headers = []): self
    {
        return new self($headers, false);
    }

    /**
     * @param int|string|null $traceId
     *
     * @return self
     */
    public function withTraceId($traceId): self
    {
        return new self(
            $this->headers,
            $this->isPersistent,
            $this->isMandatory,
            $this->isImmediate,
            $this->expiredAfter,
            $traceId
        );
    }

    /**
     * @param string           $key
     * @param string|int|float $value
     *
     * @return DefaultDeliveryOptions
     */
    public function withHeader(string $key, $value): self
    {
        /** @var array<string, string|int|float> $headers */
        $headers       = $this->headers;
        $headers[$key] = $value;

        return new self(
            $headers,
            $this->isPersistent,
            $this->isMandatory,
            $this->isImmediate,
            $this->expiredAfter,
            $this->traceId
        );
    }

    /**
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
        bool $isMandatory = false,
        bool $isImmediate = false,
        ?int $expiredAfter = null,
        $traceId = null
    )
    {
        $this->headers      = $headers;
        $this->isPersistent = $isPersistent;
        $this->isMandatory  = $isMandatory;
        $this->isImmediate  = $isImmediate;
        $this->expiredAfter = $expiredAfter;
        $this->traceId      = $traceId;
    }
}
