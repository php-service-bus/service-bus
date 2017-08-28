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

namespace Desperado\ConcurrencyFramework\Infrastructure\Serializer\Exceptions;

use Desperado\ConcurrencyFramework\Domain\Messages\Exceptions\MessageSerializerException;

/**
 *
 */
class MessageSerializationFailException extends MessageSerializerException
{

}
