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

namespace Desperado\ConcurrencyFramework\Infrastructure\CQRS\Annotation;

use Desperado\ConcurrencyFramework\Domain\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Service extends AbstractAnnotation
{
    /**
     * Logger channel
     *
     * @var string
     */
    public $loggerChannel = '';
}
