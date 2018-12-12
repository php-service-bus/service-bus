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
     * @var string|null
     */
    public $traceId;

    /**
     * @param array<string, string|int|float> $headers
     *
     * @return self
     */
    public static function nonPersistent(array $headers = []): self
    {
        $self               = new self($headers);
        $self->isPersistent = false;

        return $self;
    }

    /**
     * @param array<string, string|int|float> $headers
     */
    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }
}
