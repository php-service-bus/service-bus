<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Domain\Messages;

use Desperado\ConcurrencyFramework\Domain\ParameterBag;

/**
 * Received message DTO
 */
class ReceivedMessage
{
    /**
     * Message
     *
     * @var MessageInterface
     */
    private $message;

    /**
     * Metadata (ex. headers)
     *
     * @var ParameterBag
     */
    private $metadata;

    /**
     * @param MessageInterface $message
     * @param ParameterBag     $metadata
     */
    public function __construct(MessageInterface $message, ParameterBag $metadata)
    {
        $this->message = $message;
        $this->metadata = $metadata;
    }

    /**
     * Get message
     *
     * @return MessageInterface
     */
    public function getMessage(): MessageInterface
    {
        return $this->message;
    }

    /**
     * Get metadata bag
     *
     * @return ParameterBag
     */
    public function getMetadata(): ParameterBag
    {
        return $this->metadata;
    }
}
