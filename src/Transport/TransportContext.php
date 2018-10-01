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
 *
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
     * @var int
     */
    private $receivedAt;

    /**
     * @param array $headers
     *
     * @return self
     */
    public static function messageReceived(): self
    {
        return new self();
    }

    /**
     * Close constructor
     */
    private function __construct()
    {
        $this->id         = uuid();
        $this->receivedAt = \microtime(true) * 10000;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function receivedAt(): int
    {
        return $this->receivedAt;
    }
}
