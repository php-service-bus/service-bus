<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Sagas\Configuration;

/**
 * Specified for each listener options
 */
final class SagaListenerOptions
{
    /**
     * If a value is specified for a particular listener, then it will be used. Otherwise, the value will be obtained
     * from the global parameters of the saga
     *
     * @var string|null
     */
    private $containingIdentifierProperty;

    /**
     * Basic information about saga
     *
     * @var SagaMetadata
     */
    private $sagaMetadata;

    /**
     * @param string       $containingIdentifierProperty
     * @param SagaMetadata $metadata
     *
     * @return self
     */
    public static function withCustomContainingIdentifierProperty(string $containingIdentifierProperty, SagaMetadata $metadata): self
    {
        $self = new self($metadata);

        $self->containingIdentifierProperty = $containingIdentifierProperty;

        return $self;
    }

    /**
     * @param SagaMetadata $metadata
     *
     * @return SagaListenerOptions
     */
    public static function withGlobalOptions(SagaMetadata $metadata): self
    {
        return new self($metadata);
    }

    /**
     * Receive saga class
     *
     * @return string
     */
    public function sagaClass(): string
    {
        return $this->sagaMetadata->sagaClass;
    }

    /**
     * Receive identifier class
     *
     * @return string
     */
    public function identifierClass(): string
    {
        return $this->sagaMetadata->identifierClass;
    }

    /**
     * Receive the name of the event property that contains the saga ID
     *
     * @return string
     */
    public function containingIdentifierProperty(): string
    {
        if(null !== $this->containingIdentifierProperty && '' !== $this->containingIdentifierProperty)
        {
            return $this->containingIdentifierProperty;
        }

        return $this->sagaMetadata->containingIdentifierProperty;
    }

    /**
     * @param SagaMetadata $sagaMetadata
     */
    private function __construct(SagaMetadata $sagaMetadata)
    {
        $this->sagaMetadata = $sagaMetadata;
    }
}
