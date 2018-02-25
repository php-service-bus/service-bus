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
 * Saga metadata
 */
class SagaMetadata
{
    /**
     * Saga namespace
     *
     * @var string
     */
    private $sagaNamespace;

    /**
     * Expire date modifier
     *
     * @var string
     */
    private $expireDateModifier;

    /**
     * Identifier class namespace
     *
     * @var string
     */
    private $identifierClass;

    /**
     * The property of the event, which must contain the saga id
     *
     * @var string
     */
    private $containingIdentifierProperty;

    /**
     * Event listeners
     *
     * [
     *    'someEventNamespace' => object SagaListener
     * ]
     *
     * @var SagaListener[]
     */
    private $listeners;

    /**
     * @param string $sagaNamespace
     * @param string $expireDateModifier
     * @param string $identifierClass
     * @param string $containingIdentifierProperty
     *
     * @return self
     */
    public static function create(
        string $sagaNamespace,
        string $expireDateModifier,
        string $identifierClass,
        string $containingIdentifierProperty
    ): self
    {
        $self = new self();

        $self->sagaNamespace = $sagaNamespace;
        $self->expireDateModifier = $expireDateModifier;
        $self->identifierClass = $identifierClass;
        $self->containingIdentifierProperty = $containingIdentifierProperty;

        return $self;
    }

    /**
     * Append saga event listener
     *
     * @param SagaListener $listener
     *
     * @return void
     */
    public function appendListener(SagaListener $listener): void
    {
        $this->listeners[$listener->getEventNamespace()] = $listener;
    }

    /**
     * Get saga namespace
     *
     * @return string
     */
    public function getSagaNamespace(): string
    {
        return $this->sagaNamespace;
    }

    /**
     * Get expire date modifier
     *
     * @return string
     */
    public function getExpireDateModifier(): string
    {
        return $this->expireDateModifier;
    }

    /**
     * Get identifier class namespace
     *
     * @return string
     */
    public function getIdentifierClass(): string
    {
        return $this->identifierClass;
    }

    /**
     * Get property of the event, which must contain the saga id
     *
     * @return string
     */
    public function getContainingIdentifierProperty(): string
    {
        return $this->containingIdentifierProperty;
    }

    /**
     * Get listeners collection
     *
     * @return SagaListener[]
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {
        $this->listeners = [];
    }
}
