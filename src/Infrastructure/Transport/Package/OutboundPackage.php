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
use Desperado\ServiceBus\Endpoint\TransportLevelDestination;

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
     * @var array<string, string|int|float>
     */
    private $headers;

    /**
     * Message destination
     *
     * @var TransportLevelDestination
     */
    private $destination;

    /**
     * The message must be stored in the broker
     *
     * @var bool
     */
    private $persistentFlag = false;

    /**
     * The message must be sent to the existing recipient
     *
     * @var bool
     */
    private $mandatoryFlag = false;

    /**
     * The message will be sent with the highest priority
     *
     * @var bool
     */
    private $immediateFlag = false;

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
     * @param InputStream                     $payload
     * @param array<string, string|int|float> $headers
     * @param TransportLevelDestination       $destination
     */
    public function __construct(InputStream $payload, array $headers, TransportLevelDestination $destination)
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
        return $this->persistentFlag;
    }

    /**
     * @return bool
     */
    public function isMandatory(): bool
    {
        return $this->mandatoryFlag;
    }

    /**
     * @return bool
     */
    public function isImmediate(): bool
    {
        return $this->immediateFlag;
    }

    /**
     * @return int|null
     */
    public function expiredAfter(): ?int
    {
        return $this->expiredAfter;
    }

    /**
     * To whom the message will be sent
     *
     * @return TransportLevelDestination
     */
    public function destination(): TransportLevelDestination
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
     *
     * @param string $id
     *
     * @return void
     */
    public function withTraceId(string $id): void
    {
        $this->traceId = $id;
    }

    /**
     * Save message in broker
     *
     * @param bool $isPersistent
     *
     * @return void
     */
    public function setPersistentFlag(bool $isPersistent): void
    {
        $this->persistentFlag = $isPersistent;
    }

    /**
     * When publishing a message, the message must be routed to a valid queue. If it is not, an error will be returned
     *
     * @param bool $isMandatory
     *
     * @return void
     */
    public function setMandatoryFlag(bool $isMandatory): void
    {
        $this->mandatoryFlag = $isMandatory;
    }

    /**
     * When publishing a message, mark this message for immediate processing by the broker (High priority message)
     *
     * @param bool $isImmediate
     *
     * @return void
     */
    public function setImmediateFlag(bool $isImmediate): void
    {
        $this->immediateFlag = $isImmediate;
    }

    /**
     * Setup message TTL
     *
     * @param int|null $expiredAfter
     *
     * @return void
     */
    public function setExpiredAfter(?int $expiredAfter): void
    {
        $this->expiredAfter = $expiredAfter;
    }
}
