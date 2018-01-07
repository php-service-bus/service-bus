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
 * Options for sending a message to the transport layer
 */
class MessageDeliveryOptions
{
    /**
     * Destination exchange
     *
     * @var string|null
     */
    private $destination;

    /**
     * Routing key
     *
     * @var string|null
     */
    private $routingKey;

    /**
     * Headers bag
     *
     * @var ParameterBag
     */
    private $headers;

    /**
     * @param null|string       $destination
     * @param null|string       $routingKey
     * @param ParameterBag|null $headersBag
     *
     * @return MessageDeliveryOptions
     */
    public static function create(?string $destination = null, ?string $routingKey = null, ParameterBag $headersBag = null): self
    {
        $self = new self();

        $self->destination = $destination;
        $self->routingKey = $routingKey;
        $self->headers = $headersBag ?? new ParameterBag();

        return $self;
    }

    /**
     * Has specified destination
     *
     * @return bool
     */
    public function destinationSpecified(): bool
    {
        return '' !== (string) $this->destination;
    }

    /**
     * Has specified routing key
     *
     * @return bool
     */
    public function routingKeySpecified(): bool
    {
        return '' !== (string) $this->routingKey;
    }

    /**
     * Change delivery destination
     *
     * @param string      $destination
     * @param string|null $routingKey
     *
     * @return MessageDeliveryOptions
     */
    public function changeDestination(string $destination, ?string $routingKey = null): self
    {
        return self::create($destination, $routingKey, $this->headers);
    }

    /**
     * Get headers
     *
     * @return ParameterBag
     */
    public function getHeaders(): ParameterBag
    {
        return $this->headers;
    }

    /**
     * Get routing key
     *
     * @return string|null
     */
    public function getRoutingKey(): ?string
    {
        return (string) $this->routingKey;
    }

    /**
     * Get message destination
     *
     * @return string
     */
    public function getDestination(): string
    {
        return (string) $this->destination;
    }
}
