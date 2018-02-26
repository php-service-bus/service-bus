<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Configuration;

/**
 * Saga listener
 */
final class SagaListenerConfiguration
{
    /**
     * Saga class namespace
     *
     * @var string
     */
    private $sagaClass;

    /**
     * Event class namespace
     *
     * @var string
     */
    private $eventClass;

    /**
     * The event property that contains the saga ID
     *
     * @var string
     */
    private $containingIdentifierProperty;

    /**
     * @param string $sagaClass
     * @param string $eventClass
     * @param string $containingIdentifierProperty
     *
     * @return SagaListenerConfiguration
     */
    public static function create(string $sagaClass, string $eventClass, string $containingIdentifierProperty): self
    {
        $self = new self();

        $self->sagaClass = $sagaClass;
        $self->eventClass = $eventClass;
        $self->containingIdentifierProperty = $containingIdentifierProperty;

        return $self;
    }

    /**
     * Get saga class namespace
     *
     * @return string
     */
    public function getSagaClass(): string
    {
        return $this->sagaClass;
    }

    /**
     * Get event class namespace
     *
     * @return string
     */
    public function getEventClass(): string
    {
        return $this->eventClass;
    }

    /**
     * Get event property that contains the saga ID
     *
     * @return string
     */
    public function getContainingIdentifierProperty(): string
    {
        return $this->containingIdentifierProperty;
    }

    /**
     * Is custom event property that contains the saga ID specified
     *
     * @return bool
     */
    public function hasCustomIdentifierProperty(): bool
    {
        return '' !== $this->containingIdentifierProperty;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
