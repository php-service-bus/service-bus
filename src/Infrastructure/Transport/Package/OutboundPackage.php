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

use Desperado\ServiceBus\Endpoint\TransportLevelDestination;

/**
 * Outbound package
 */
final class OutboundPackage
{
    /**
     * Message body
     *
     * @var string
     */
    public $payload;

    /**
     * Message headers
     *
     * @var array<string, string|int|float>
     */
    public $headers;

    /**
     * Message destination
     *
     * @var TransportLevelDestination
     */
    public $destination;

    /**
     * The message must be stored in the broker
     *
     * @var bool
     */
    public $persistentFlag = false;

    /**
     * The message must be sent to the existing recipient
     *
     * @var bool
     */
    public $mandatoryFlag = false;

    /**
     * The message will be sent with the highest priority
     *
     * @var bool
     */
    public $immediateFlag = false;

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
     * @param string                          $payload
     * @param array<string, string|int|float> $headers
     * @param TransportLevelDestination       $destination
     */
    public function __construct(string $payload, array $headers, TransportLevelDestination $destination)
    {
        $this->payload     = $payload;
        $this->headers     = $headers;
        $this->destination = $destination;
    }
}
