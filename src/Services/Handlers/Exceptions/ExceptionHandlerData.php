<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Handlers\Exceptions;

/**
 * Information about the exception handler
 */
class ExceptionHandlerData
{
    /**
     * Exception class namespace
     *
     * @var string
     */
    private $exceptionClassNamespace;

    /**
     * Message class namespace
     *
     * @var string
     */
    private $messageClassNamespace;

    /**
     * Handler closure
     *
     * @var \Closure
     */
    private $exceptionHandler;

    /**
     * Exception handling options
     *
     * @var ExceptionHandlingParameters
     */
    private $exceptionHandlingParameters;

    /**
     * @param string                      $exceptionClassNamespace
     * @param string                      $messageClassNamespace
     * @param \Closure                    $exceptionHandler
     * @param ExceptionHandlingParameters $exceptionHandlingParameters
     *
     * @return ExceptionHandlerData
     */
    public static function new(
        string $exceptionClassNamespace,
        string $messageClassNamespace,
        \Closure $exceptionHandler,
        ExceptionHandlingParameters $exceptionHandlingParameters
    ): self
    {
        $self = new self();

        $self->exceptionClassNamespace = \ltrim($exceptionClassNamespace, '\\');
        $self->messageClassNamespace = \ltrim($messageClassNamespace, '\\');
        $self->exceptionHandler = $exceptionHandler;
        $self->exceptionHandlingParameters = $exceptionHandlingParameters;

        return $self;
    }

    /**
     * Get exception class namespace
     *
     * @return string
     */
    public function getExceptionClassNamespace(): string
    {
        return $this->exceptionClassNamespace;
    }

    /**
     * Get message class namespace
     *
     * @return string
     */
    public function getMessageClassNamespace(): string
    {
        return $this->messageClassNamespace;
    }

    /**
     * Get exception handler
     *
     * @return \Closure
     */
    public function getExceptionHandler(): \Closure
    {
        return $this->exceptionHandler;
    }

    /**
     * Get exception handling options
     *
     * @return ExceptionHandlingParameters
     */
    public function getExceptionHandlingParameters(): ExceptionHandlingParameters
    {
        return $this->exceptionHandlingParameters;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
