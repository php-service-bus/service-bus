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

namespace Desperado\ConcurrencyFramework\Domain\Messages\Exceptions;

use Desperado\ConcurrencyFramework\Domain\AbstractConcurrencyFrameworkException;

/**
 *
 */
class MessageSerializerException extends AbstractConcurrencyFrameworkException
{

}
