<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\AmqpExt;

/**
 * Amqp-specified message properties
 */
final class AmqpMessageProperties
{
    /**
     * @see \AMQP_MANDATORY
     * @see \AMQP_IMMEDIATE
     *
     * @var int
     */
    private $flags = \AMQP_NOPARAM;

    /**
     * Content-type
     *
     * @var string
     */
    private $contentType = 'application/json';

    /**
     * Encoding
     *
     * @var string
     */
    private $contentEncoding = 'UTF-8';

    /**
     * Message identifier
     *
     * @var string|null
     */
    private $messageId;

    /**
     * Client identifier
     *
     * @var string|null
     */
    private $userId;

    /**
     * Application identifier
     *
     * @var string|null
     */
    private $appId;

    /**
     * @see \AMQP_DURABLE
     *
     * @var int|null
     */
    private $deliveryMode;

    /**
     * The message priority field is defined as an unsigned byte, so in practice priorities should be between 0 and 255.
     *
     * @var int
     */
    private $priority = 0;

    /**
     * Message ttl (in milliseconds)
     *
     * @var int|null
     */
    private $expiration;

    /**
     * @return self
     */
    public function makePersistent(): self
    {
        $this->deliveryMode = \AMQP_DURABLE;

        return $this;
    }

    /**
     * @return self
     */
    public function makeMandatory(): self
    {
        $this->flags += \AMQP_MANDATORY;

        return $this;
    }

    /**
     * @return self
     */
    public function makeImmediate(): self
    {
        $this->flags += \AMQP_IMMEDIATE;

        return $this;
    }

    /**
     * @param string $contentType
     * @param string $contentEncoding
     *
     * @return self
     */
    public function setupContentType(string $contentType, string $contentEncoding): self
    {
        $this->contentType     = $contentType;
        $this->contentEncoding = $contentEncoding;

        return $this;
    }

    /**
     * @param int $priorityValue
     *
     * @return self
     */
    public function changePriority(int $priorityValue): self
    {
        $this->priority = $priorityValue;

        return $this;
    }

    /**
     * @param string $applicationId
     *
     * @return self
     */
    public function setupApplication(string $applicationId): self
    {
        $this->appId = $applicationId;

        return $this;
    }

    /**
     * @param string $userId
     *
     * @return self
     */
    public function setupUserId(string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @param string $messageId
     *
     * @return self
     */
    public function setupMessageId(string $messageId): self
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * @param int $milliseconds
     *
     * @return self
     */
    public function makeExpiredAfter(int $milliseconds): self
    {
        $this->expiration = $milliseconds;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return \array_filter([
            'content_type'     => $this->contentType,
            'content_encoding' => $this->contentEncoding,
            'message_id'       => $this->messageId,
            'user_id'          => $this->userId,
            'app_id'           => $this->appId,
            'delivery_mode'    => $this->deliveryMode,
            'priority'         => $this->priority,
            'expiration'       => $this->expiration
        ]);
    }
}
