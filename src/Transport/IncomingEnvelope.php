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

use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 *
 */
final class IncomingEnvelope
{
    /**
     * Serialized message (received)
     *
     * @var string
     */
    private $plain;

    /**
     * Denormalized message representation
     *
     * @var Message
     */
    private $decoded;

    /**
     * Custom headers
     *
     * @var array<string, string>
     */
    private $headers;

    /**
     * @param string  $operationId
     * @param string  $plain
     * @param Message $decoded
     * @param array   $headers
     */
    public function __construct(string $plain, Message $decoded, array $headers = [])
    {
        $this->plain       = $plain;
        $this->decoded     = $decoded;
        $this->headers     = $headers;
    }

    /**
     * Receive serialized (request) message representation
     *
     * @return string
     */
    public function requestBody(): string
    {
        return $this->plain;
    }

    /**
     * Receive denormalized message representation
     *
     * @return Message
     */
    public function denormalized(): Message
    {
        return $this->decoded;
    }

    /**
     * Receive custom headers
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->headers;
    }
}
