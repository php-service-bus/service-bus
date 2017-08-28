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

namespace Desperado\ConcurrencyFramework\Infrastructure\Annotation\Marker;

use Desperado\ConcurrencyFramework\Infrastructure\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class AggregateRootAnnotation extends AbstractAnnotation
{

}
