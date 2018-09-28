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

namespace Desperado\ServiceBus\Transport\Marshal;

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
    public $message;

    /**
     * Message class
     *
     * @var string
     */
    public $namespace;

    /**
     * @param array  $message
     * @param string $messageClass
     */
    public function __construct(array $message, string $messageClass)
    {
        $this->message   = $message;
        $this->namespace = $messageClass;
    }
}
