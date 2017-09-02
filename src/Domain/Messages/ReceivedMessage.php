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

namespace Desperado\Framework\Domain\Messages;

use Desperado\Framework\Domain\ParameterBag;

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
