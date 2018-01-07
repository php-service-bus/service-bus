<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services\Handlers\Messages;

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
     * Execution options
     *
     * @var AbstractMessageExecutionParameters
     */
    private $executionOptions;

    /**
     * @param string                             $messageClassNamespace
     * @param \Closure                           $messageHandler
     * @param AbstractMessageExecutionParameters $executionOptions
     *
     * @return MessageHandlerData
     */
    public static function new(
        string $messageClassNamespace,
        \Closure $messageHandler,
        AbstractMessageExecutionParameters $executionOptions
    ): self
    {
        $self = new self();

        $self->messageClassNamespace = $messageClassNamespace;
        $self->messageHandler = $messageHandler;
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
