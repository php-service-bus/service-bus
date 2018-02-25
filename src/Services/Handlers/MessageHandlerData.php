<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Handlers;

/**
 * Information about the message handler
 */
class MessageHandlerData
{
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
    private $messageHandler;

    /**
     * Autowiring services
     *
     * @var object[]
     */
    private $autowiringServices;

    /**
     * Execution options
     *
     * @var AbstractMessageExecutionParameters
     */
    private $executionOptions;

    /**
     * @param string                             $messageClassNamespace
     * @param \Closure                           $messageHandler
     * @param array                              $autowiringServices
     * @param AbstractMessageExecutionParameters $executionOptions
     *
     * @return self
     */
    public static function new(
        string $messageClassNamespace,
        \Closure $messageHandler,
        array $autowiringServices,
        AbstractMessageExecutionParameters $executionOptions
    ): self
    {
        $self = new self();

        $self->messageClassNamespace = $messageClassNamespace;
        $self->messageHandler = $messageHandler;
        $self->autowiringServices = $autowiringServices;
        $self->executionOptions = $executionOptions;

        return $self;
    }

    /**
     * Get message namespace
     *
     * @return string
     */
    public function getMessageClassNamespace(): string
    {
        return $this->messageClassNamespace;
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
     * Get execution handler
     *
     * @return \Closure
     */
    public function getMessageHandler(): \Closure
    {
        return $this->messageHandler;
    }

    /**
     * Get execution options
     *
     * @return AbstractMessageExecutionParameters
     */
    public function getExecutionOptions(): AbstractMessageExecutionParameters
    {
        return $this->executionOptions;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
