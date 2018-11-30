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

use Amp\Promise;
use Desperado\ServiceBus\Endpoint\TransportLevelDestination;

/**
 *
 */
interface IncomingPackage
{
    /**
     * Receive package id
     *
     * @return string
     */
    public function id(): string;

    /**
     * Receive Unix timestamp with microseconds (the time the message was received)
     *
     * @return float
     */
    public function time(): float;

    /**
     * The source from which the message was received
     *
     * @return TransportLevelDestination
     */
    public function origin(): TransportLevelDestination;

    /**
     * Receive message body
     *
     * @return string
     */
    public function payload(): string ;

    /**
     * Receive message headers bag
     *
     * @return array<string, string>
     */
    public function headers(): array;

    /**
     * Acks given message
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\AcknowledgeFailed
     */
    public function ack(): Promise;

    /**
     * Nacks message
     *
     * @param bool        $requeue    Send back to the queue
     * @param null|string $withReason Reason for refusal
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\NotAcknowledgeFailed
     */
    public function nack(bool $requeue, ?string $withReason = null): Promise;

    /**
     * Rejects message
     *
     * @param bool        $requeue    Send back to the queue
     * @param null|string $withReason Reason for refusal
     *
     * @return Promise It does not return any result
     *
     * @throws \Desperado\ServiceBus\Infrastructure\Transport\Exceptions\RejectFailed
     */
    public function reject(bool $requeue, ?string $withReason = null): Promise;

    /**
     * Receive trace id
     *
     * @return string
     */
    public function traceId(): string;
}
