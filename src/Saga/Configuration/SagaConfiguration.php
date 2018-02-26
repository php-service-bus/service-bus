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
 * Saga configuration options
 */
final class SagaConfiguration
{
    /**
     * Saga namespace
     *
     * @var string
     */
    private $sagaNamespace;

    /**
     * Saga expire date modifier
     *
     * @see http://php.net/manual/ru/datetime.formats.relative.php
     *
     * @var string
     */
    private $expireDateModifier;

    /**
     * Saga identifier class namespace
     *
     * @var string
     */
    private $identifierNamespace;

    /**
     * The event property that contains the saga ID
     *
     * @var string
     */
    private $containingIdentifierProperty;

    /**
     * @param string string $sagaNamespace
     * @param string $expireDateModifier
     * @param string $identifierNamespace
     * @param string $containingIdentifierProperty
     *
     * @return SagaConfiguration
     */
    public static function create(
        string $sagaNamespace,
        string $expireDateModifier,
        string $identifierNamespace,
        string $containingIdentifierProperty
    ): self
    {
        $self = new self();

        $self->sagaNamespace = $sagaNamespace;
        $self->expireDateModifier = $expireDateModifier;
        $self->identifierNamespace = $identifierNamespace;
        $self->containingIdentifierProperty = $containingIdentifierProperty;

        return $self;
    }

    /**
     * Get saga class namespace
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
    public function getIdentifierNamespace(): string
    {
        return $this->identifierNamespace;
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
     * Close constructor
     */
    private function __construct()
    {

    }
}
