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

namespace Desperado\Framework\Infrastructure\CQRS\Annotation;

use Desperado\Framework\Domain\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class CommandHandler extends AbstractAnnotation
{
    /**
     * Retry delay
     *
     * @var float
     */
    public $retryDelay = 0.2;

    /**
     * Retry count
     *
     * @var int
     */
    public $retryCount = 5;

    /**
     * Logger channel
     *
     * @var string
     */
    public $loggerChannel = '';

    /**
     * Log payload data
     *
     * @var bool
     */
    public $logPayload = false;
}
