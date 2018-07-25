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

namespace Desperado\ServiceBus\MessageBus\Contracts;

use Desperado\ServiceBus\Common\Contract\Messages\Event;
use Desperado\ServiceBus\Common\Contract\Messages\Message;

/**
 * Message validation error
 */
class MessageValidationFailed implements Event
{
    /**
     * Message class
     *
     * @var string
     */
    private $messageClass;

    /**
     * Original message payload
     *
     * @var array
     */
    private $messagePayload;

    /**
     * Violations
     *
     * [
     *    'propertyKey' => [
     *        0 => [
     *            'reasonMessage'
     *        ],
     *        ....
     *    ],
     *    ...
     * ]
     *
     * @var array<string, array<int, string>>
     */
    private $violations = [];

    /**
     * @param Message $message
     * @param array   $payload
     *
     * @return self
     */
    public static function create(Message $message, array $payload): self
    {
        $self = new self();

        $self->messageClass   = \get_class($message);
        $self->messagePayload = $payload;

        return $self;
    }

    /**
     * @param string $propertyPath
     * @param string $reason
     *
     * @return void
     */
    public function addViolation(string $propertyPath, string $reason): void
    {
        $this->violations[$propertyPath][] = $reason;
    }
}
