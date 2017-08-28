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

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Storage\Configuration;

use Desperado\ConcurrencyFramework\Domain\AbstractConcurrencyFrameworkException;

/**
 *
 */
class UnSupportedStorageDriverException extends AbstractConcurrencyFrameworkException
{

}
