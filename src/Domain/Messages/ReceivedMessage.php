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
    public $message;

    /**
     * Metadata (ex. headers)
     *
     * @var ParameterBag
     */
    public $metadata;
}
