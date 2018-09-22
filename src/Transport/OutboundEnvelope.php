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

namespace Desperado\ServiceBus\Transport;

/**
 *
 */
interface OutboundEnvelope
{
    /**
     * Receive message content
     *
     * @return string
     */
    public function messageContent(): string;

    /**
     * Receive message headers
     *
     * @return array
     */
    public function headers(): array;

    /**
     * Change message content-type
     *
     * @param string $contentType
     *
     * @return void
     */
    public function changeContentType(string $contentType): void;

    /**
     * Change message content-encoding
     *
     * @param string $contentEncoding
     *
     * @return void
     */
    public function changeContentEncoding(string $contentEncoding): void;

    /**
     * When publishing a message, the message must be routed to a valid queue. If it is not, an error will be returned
     *
     * @return void
     */
    public function makeMandatory(): void;

    /**
     * Discard an exception if the specified destination does not exist
     *
     * @return bool
     */
    public function isMandatory(): bool;

    /**
     * When publishing a message, mark this message for immediate processing by the broker (High priority message)
     *
     * @return void
     */
    public function makeImmediate(): void;

    /**
     * Is high priority message
     *
     * @return bool
     */
    public function isImmediate(): bool;

    /**
     * Save message in broker
     *
     * @return void
     */
    public function makePersistent(): void;

    /**
     * Change message execution priority
     *
     * @param int $priority
     *
     * @return void
     */
    public function changePriority(int $priority): void;

    /**
     * Setup message TTL
     *
     * @param int $milliseconds
     *
     * @return void
     */
    public function makeExpiredAfter(int $milliseconds): void;

    /**
     * Setup client identifier
     *
     * @param string $clientId
     *
     * @return mixed
     */
    public function setupClientId(string $clientId): void;

    /**
     * Setup app identifier
     *
     * @param string $appId
     *
     * @return void
     */
    public function setupAppId(string $appId): void;

    /**
     * Setup message identifier
     *
     * @param string $messageId
     *
     * @return void
     */
    public function setupMessageId(string $messageId): void;

    /**
     * Receive message id
     *
     * @return string|null
     */
    public function messageId(): ?string;

    /**
     * Receive client id
     *
     * @return null|string
     */
    public function clientId(): ?string;

    /**
     * Receive application id
     *
     * @return null|string
     */
    public function appId(): ?string;

    /**
     * Receive expiration time in milliseconds
     *
     * @return int|null
     */
    public function expirationTime(): ?int;

    /**
     * Receive message priority
     *
     * @return int
     */
    public function priority(): int;

    /**
     * Is persistence message
     *
     * @return bool
     */
    public function isPersistent(): bool;

    /**
     * Message encoding
     *
     * @return string
     */
    public function contentEncoding(): string;

    /**
     * Message content-type
     *
     * @return string
     */
    public function contentType(): string;
}
