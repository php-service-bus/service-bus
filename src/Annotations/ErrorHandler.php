<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations;

use Desperado\Domain\Annotations\AbstractAnnotation;

/**
 * Annotation indicating to the exceptions handler
 *
 * @Annotation
 * @Target("METHOD")
 */
class ErrorHandler extends AbstractAnnotation
{
    /**
     * Logger channel
     *
     * @var string|null
     */
    protected $loggerChannel;

    /**
     * Get exception-specific logger channel
     *
     * @return string|null
     */
    public function getLoggerChannel(): ?string
    {
        return $this->loggerChannel;
    }
}
