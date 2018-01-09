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
     * Message namespace
     *
     * @var string|null
     */
    protected $message;

    /**
     * Type of exception to be processed
     *
     * @var string|null
     */
    protected $type;

    /**
     * Get exception-specific logger channel
     *
     * @return string|null
     */
    public function getLoggerChannel(): ?string
    {
        return $this->loggerChannel;
    }

    /**
     * Get message namespace
     *
     * @return string|null
     */
    public function getMessageClass(): ?string
    {
        return $this->message;
    }

    /**
     * Get type of exception to be processed
     *
     * @return string
     */
    public function getThrowableType(): string
    {
        return $this->type ?? \Throwable::class;
    }
}
