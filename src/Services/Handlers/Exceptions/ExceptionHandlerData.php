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
     * Autowiring services
     *
     * @var object[]
     */
    private $autowiringServices;

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
     * @param array $autowiringServices
     * @param ExceptionHandlingParameters $exceptionHandlingParameters
     *
     * @return ExceptionHandlerData
     */
    public static function new(
        string $exceptionClassNamespace,
        string $messageClassNamespace,
        \Closure $exceptionHandler,
        array $autowiringServices,
        ExceptionHandlingParameters $exceptionHandlingParameters
    ): self
    {
        $self = new self();

        $self->exceptionClassNamespace = \ltrim($exceptionClassNamespace, '\\');
        $self->messageClassNamespace = \ltrim($messageClassNamespace, '\\');
        $self->exceptionHandler = $exceptionHandler;
        $self->autowiringServices = $autowiringServices;
        $self->exceptionHandlingParameters = $exceptionHandlingParameters;

        return $self;
    }

    /**
     * Get a list of services that will be prepended to the arguments of the handler
     *
     * @return object[]
     */
    public function getAutowiringServices(): array
    {
        return $this->autowiringServices;
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
