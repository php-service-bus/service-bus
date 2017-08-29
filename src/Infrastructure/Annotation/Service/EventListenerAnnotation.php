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

namespace Desperado\ConcurrencyFramework\Infrastructure\Annotation\Service;

use Desperado\ConcurrencyFramework\Infrastructure\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class EventListenerAnnotation extends AbstractAnnotation
{
    /**
     * Logger channel
     *
     * @var string
     */
    public $loggerChannel = '';
}
