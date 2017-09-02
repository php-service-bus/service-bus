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

namespace Desperado\ConcurrencyFramework\Domain\Serializer;

use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\ReceivedMessage;
use Desperado\ConcurrencyFramework\Domain\ParameterBag;

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
     * @throws \Desperado\ConcurrencyFramework\Domain\Serializer\Exceptions\MessageSerializationFailException
     */
    public function serialize(MessageInterface $message, ParameterBag $metadata = null): string;

    /**
     * Unserialize message
     *
     * @param string $content
     *
     * @return ReceivedMessage
     *
     * @throws \Desperado\ConcurrencyFramework\Domain\Serializer\Exceptions\MessageSerializationFailException
     */
    public function unserialize(string $content): ReceivedMessage;
}
