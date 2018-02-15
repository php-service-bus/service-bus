<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Metadata;

/**
 * Saga event listener data
 */
class SagaListener
{
    /**
     * The event on which the saga is signed
     *
     * @var string
     */
    private $eventNamespace;

    /**
     * Event handler
     *
     * @var \Closure
     */
    private $handler;

    /**
     * @param string   $eventNamespace
     * @param \Closure $closure
     *
     * @return SagaListener
     */
    public static function new(string $eventNamespace, \Closure $closure): self
    {
        $self = new self();

        $self->eventNamespace = $eventNamespace;
        $self->handler = $closure;

        return $self;
    }

    /**
     * Get event on which the saga is signed
     *
     * @return string
     */
    public function getEventNamespace(): string
    {
        return $this->eventNamespace;
    }

    /**
     * Get event handler
     *
     * @return \Closure
     */
    public function getHandler(): \Closure
    {
        return $this->handler;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
