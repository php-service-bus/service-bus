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

/**
 * A message sent over the network (in serialized representation)
 */
final class MessageDTO
{
    /**
     * Normalized object payload
     *
     * @var array
     */
    private $message;

    /**
     * Message class
     *
     * @var string
     */
    private $namespace;

    /**
     * @param array  $message
     * @param string $messageClass
     */
    public function __construct(array $message, string $messageClass)
    {
        $this->message   = $message;
        $this->namespace = $messageClass;
    }

    /**
     * Receive message normalized data
     *
     * @return array
     */
    public function payload(): array
    {
        return $this->message;
    }

    /**
     * Receive message class
     *
     * @return string
     */
    public function messageClass(): string
    {
        return $this->namespace;
    }
}
