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

namespace Desperado\ServiceBus\Transport\Amqp;

use Desperado\ServiceBus\Transport\OutboundEnvelope;

/**
 * Amqp envelope
 */
final class AmqpOutboundEnvelope implements OutboundEnvelope
{
    /**
     * Message body
     *
     * @var string
     */
    private $messageContent;

    /**
     * Message headers
     *
     * @var array
     */
    private $headers;

    /**
     * Content-type
     *
     * @var string
     */
    private $contentType = 'application/json';

    /**
     * Content encoding
     *
     * @var string
     */
    private $contentEncoding = 'UTF-8';

    /**
     * When publishing a message, the message must be routed to a valid queue. If it is not, an error will be returned
     *
     * @var bool
     */
    private $mandatory = false;

    /**
     * The message must be stored in the broker
     *
     * @var bool
     */
    private $persistent = false;

    /**
     * This is a message with the highest priority
     *
     * @var bool
     */
    private $immediate = false;

    /**
     * Message execution priority
     *
     * @var int
     */
    private $priority = 0;

    /**
     * Expiration time (in milliseconds)
     *
     * @var int|null
     */
    private $expirationTime;

    /**
     * Message identifier
     *
     * @var string|null
     */
    private $messageId;

    /**
     * Application identifier
     *
     * @var string|null
     */
    private $appId;

    /**
     * User identifier
     *
     * @var string|null
     */
    private $userId;

    /**
     * @param string $content
     * @param array  $headers
     */
    public function __construct(string $content, array $headers = [])
    {
        $this->messageContent = $content;
        $this->headers        = $headers;
    }

    /**
     * @inheritdoc
     */
    public function changeContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * @inheritdoc
     */
    public function changeContentEncoding(string $contentEncoding): void
    {
        $this->contentEncoding = $contentEncoding;
    }

    /**
     * @inheritdoc
     */
    public function makeMandatory(): void
    {
        $this->mandatory = true;
    }

    /**
     * @inheritdoc
     */
    public function isMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * @inheritdoc
     */
    public function makeImmediate(): void
    {
        $this->immediate = true;
    }

    /**
     * @inheritDoc
     */
    public function isImmediate(): bool
    {
        return $this->immediate;
    }

    /**
     * @inheritdoc
     */
    public function makePersistent(): void
    {
        $this->persistent = true;
    }

    /**
     * @inheritdoc
     */
    public function changePriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @inheritdoc
     */
    public function makeExpiredAfter(int $milliseconds): void
    {
        $this->expirationTime = $milliseconds;
    }

    /**
     * @inheritdoc
     */
    public function setupClientId(string $clientId): void
    {
        $this->userId = $clientId;
    }

    /**
     * @inheritdoc
     */
    public function setupAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    /**
     * @inheritdoc
     */
    public function setupMessageId(string $messageId): void
    {
        $this->messageId = $messageId;
    }

    /**
     * @inheritdoc
     */
    public function messageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * @inheritdoc
     */
    public function messageContent(): string
    {
        return $this->messageContent;
    }

    /**
     * @inheritdoc
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @inheritdoc
     */
    public function attributes(): array
    {
        return \array_filter([
                'content_type'     => $this->contentType,
                'content_encoding' => $this->contentEncoding,
                'message_id'       => $this->messageId,
                'user_id'          => $this->userId,
                'app_id'           => $this->appId,
                'delivery_mode'    => true === $this->persistent ? \AMQP_DURABLE : null,
                'priority'         => $this->priority,
                'expiration'       => $this->expirationTime,
                'headers'          => $this->headers
            ]
        );
    }
}
