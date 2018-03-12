<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\Message;

use Desperado\Domain\ParameterBag;

/**
 * Message DTO
 */
class Message
{
    public const TYPE_COMMAND = 'command';
    public const TYPE_EVENT   = 'event';

    /**
     * Message body
     *
     * @var string
     */
    private $body;

    /**
     * Headers
     *
     * @var ParameterBag
     */
    private $headers;

    /**
     * Exchange
     *
     * @var string|null
     */
    private $exchange;

    /**
     * Routing key
     *
     * @var string|null
     */
    private $routingKey;

    /**
     * Message type (for publish only)
     *
     * @var string|null
     */
    private $type;

    /**
     * Create message
     *
     * @param string       $body
     * @param ParameterBag $headers
     * @param string|null  $exchange
     * @param string|null  $routingKey
     *
     * @return Message
     */
    public static function create(
        string $body,
        ParameterBag $headers,
        ?string $exchange = null,
        ?string $routingKey = null
    ): self
    {
        $self = new self();

        $self->body       = $body;
        $self->headers    = $headers;
        $self->exchange   = $exchange;
        $self->routingKey = $routingKey;

        return $self;
    }

    /**
     * Create outbound message instance
     *
     * @param string       $body
     * @param ParameterBag $headers
     * @param string|null  $exchange
     * @param string|null  $routingKey
     * @param string|null  $type
     *
     * @return Message
     */
    public static function outbound(
        string $body,
        ParameterBag $headers,
        ?string $exchange = null,
        ?string $routingKey = null,
        ?string $type = null
    ): self
    {
        $self = new self();

        $self->body       = $body;
        $self->headers    = $headers;
        $self->exchange   = $exchange;
        $self->routingKey = $routingKey;
        $self->type       = $type;

        return $self;
    }

    /**
     * Change destination exchange
     *
     * @param string $exchange
     *
     * @return self
     */
    public function changeExchange(string $exchange): self
    {
        $self           = clone $this;
        $self->exchange = $exchange;

        return $self;
    }

    /**
     * Get message type
     *
     * @return bool|null
     */
    public function getType(): ?bool
    {
        return $this->type;
    }

    /**
     * It's `command` message type
     *
     * @return bool
     */
    public function isCommand(): bool
    {
        return self::TYPE_COMMAND === $this->type;
    }

    /**
     * It's `event` message type
     *
     * @return bool
     */
    public function isEvent(): bool
    {
        return self::TYPE_EVENT === $this->type;
    }

    /**
     * Get message body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get message headers
     *
     * @return ParameterBag
     */
    public function getHeaders(): ParameterBag
    {
        return $this->headers;
    }

    /**
     * Get exchange
     *
     * @return string|null
     */
    public function getExchange(): ?string
    {
        return $this->exchange;
    }

    /**
     * Get routing key
     *
     * @return string|null
     */
    public function getRoutingKey(): ?string
    {
        return $this->routingKey;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
