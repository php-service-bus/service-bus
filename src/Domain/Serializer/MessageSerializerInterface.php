<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Domain\Serializer;

use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Domain\Messages\ReceivedMessage;
use Desperado\Framework\Domain\ParameterBag;

/**
 * Messages serializer
 */
interface MessageSerializerInterface
{
    /**
     * Serialize message
     *
     * @param MessageInterface  $message
     * @param ParameterBag|null $metadata
     *
     * @return string
     *
     * @throws \Desperado\Framework\Domain\Serializer\Exceptions\MessageSerializationFailException
     */
    public function serialize(MessageInterface $message, ParameterBag $metadata = null): string;

    /**
     * Unserialize message
     *
     * @param string $content
     *
     * @return ReceivedMessage
     *
     * @throws \Desperado\Framework\Domain\Serializer\Exceptions\MessageSerializationFailException
     */
    public function unserialize(string $content): ReceivedMessage;
}
