<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Transport\Message;

use Desperado\Domain\ParameterBag;

/**
 * Message DTO
 */
class Message
{
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
     * Create message
     *
     * @param string       $body
     * @param ParameterBag $headers
     * @param null|string  $exchange
     * @param null|string  $routingKey
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

        $self->body = $body;
        $self->headers = $headers;
        $self->exchange = $exchange;
        $self->routingKey = $routingKey;

        return $self;
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
