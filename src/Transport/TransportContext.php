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

use function Desperado\ServiceBus\Common\uuid;

/**
 * The context of the received message
 */
final class TransportContext
{
    /**
     * Received message ID
     *
     * @var string
     */
    private $id;

    /**
     * Consumer ID
     *
     * @var string
     */
    private $consumerId;

    /**
     * Date of receipt of the message
     *
     * @var int
     */
    private $receivedAt;

    /**
     * @param string $consumerId
     *
     * @return self
     */
    public static function messageReceived(string $consumerId): self
    {
        return new self($consumerId);
    }

    /**
     * Get assigned message id
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get the time of receiving of message
     *
     * @return int
     */
    public function receivedAt(): int
    {
        return $this->receivedAt;
    }

    /**
     * Get consumer id
     *
     * @return string
     */
    public function consumerId(): string
    {
        return $this->consumerId;
    }

    /**
     * @param string $consumerId
     */
    private function __construct(string $consumerId)
    {
        $this->id         = uuid();
        $this->receivedAt = \microtime(true) * 10000;
        $this->consumerId = $consumerId;
    }
}
