<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport;

use Desperado\Contracts\Common\Message;

/**
 *
 */
final class IncomingEnvelope
{
    /**
     * The identifier of the received message (generated at the time of receipt from the broker)
     *
     * @var string
     */
    private $operationId;

    /**
     * Serialized message (received)
     *
     * @var string
     */
    private $plain;

    /**
     * Normalized message representation
     *
     * @var array
     */
    private $normalized;

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
     * @param array   $normalized
     * @param Message $decoded
     * @param array   $headers
     */
    public function __construct(string $operationId, string $plain, array $normalized, Message $decoded, array $headers = [])
    {
        $this->operationId = $operationId;
        $this->plain       = $plain;
        $this->normalized  = $normalized;
        $this->decoded     = $decoded;
        $this->headers     = $headers;
    }

    /**
     * Receive identifier of the message (generated at the time of receipt from the broker)
     *
     * @return string
     */
    public function operationId(): string
    {
        return $this->operationId;
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
     * Receive normalized message representation
     *
     * @return array
     */
    public function normalized(): array
    {
        return $this->normalized;
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
