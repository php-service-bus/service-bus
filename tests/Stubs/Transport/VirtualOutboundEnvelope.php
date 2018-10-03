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

namespace Desperado\ServiceBus\Tests\Stubs\Transport;

use Desperado\ServiceBus\Transport\OutboundEnvelope;

/**
 *
 */
final class VirtualOutboundEnvelope implements OutboundEnvelope
{
    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $headers;

    /**
     * @param string $body
     * @param array  $headers
     */
    public function __construct(string $body, array $headers)
    {
        $this->body    = $body;
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    public function messageContent(): string
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @inheritDoc
     */
    public function changeContentType(string $contentType): void
    {

    }

    /**
     * @inheritDoc
     */
    public function changeContentEncoding(string $contentEncoding): void
    {

    }

    /**
     * @inheritDoc
     */
    public function makeMandatory(): void
    {

    }

    /**
     * @inheritDoc
     */
    public function isMandatory(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function makeImmediate(): void
    {

    }

    /**
     * @inheritDoc
     */
    public function isImmediate(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function makePersistent(): void
    {

    }

    /**
     * @inheritDoc
     */
    public function changePriority(int $priority): void
    {

    }

    /**
     * @inheritDoc
     */
    public function makeExpiredAfter(int $milliseconds): void
    {

    }

    /**
     * @inheritDoc
     */
    public function setupClientId(string $clientId): void
    {

    }

    /**
     * @inheritDoc
     */
    public function setupAppId(string $appId): void
    {

    }

    /**
     * @inheritDoc
     */
    public function setupMessageId(string $messageId): void
    {

    }

    /**
     * @inheritDoc
     */
    public function messageId(): ?string
    {
        return null;
    }

    public function clientId(): ?string
    {
        return null;
    }

    public function appId(): ?string
    {
        return null;
    }

    public function expirationTime(): ?int
    {
        return null;
    }

    public function priority(): int
    {
        return 0;
    }

    public function isPersistent(): bool
    {
        return false;
    }

    public function contentEncoding(): string
    {
        return '';
    }

    public function contentType(): string
    {
        return '';
    }


}
