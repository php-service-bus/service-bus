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

/**
 * Messages serializer
 */
interface MessageSerializerInterface
{
    /**
     * Serialize message
     *
     * @param MessageInterface $content
     *
     * @return string
     */
    public function serialize(MessageInterface $content): string;

    /**
     * Unserialize message
     *
     * @param string $content
     *
     * @return ReceivedMessage
     */
    public function unserialize(string $content): ReceivedMessage;
}
