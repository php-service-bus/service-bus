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
 * Basic information about saga
 */
final class SagaMetadata
{
    public const DEFAULT_EXPIRE_INTERVAL = '+1 hour';

    /**
     * @var string
     */
    private $sagaClass;

    /**
     * Identifier class
     *
     * @var string
     */
    private $identifierClass;

    /**
     * The field that contains the saga identifier
     *
     * @var string
     */
    private $containingIdentifierProperty;

    /**
     * Saga expire date modifier
     *
     * @see http://php.net/manual/ru/datetime.formats.relative.php
     *
     * @var string
     */
    private $expireDateModifier;

    /**
     * @param string $sagaClass
     * @param string $identifierClass
     * @param string $containingIdentifierProperty
     * @param string $expireDateModifier
     */
    public function __construct(
        string $sagaClass,
        string $identifierClass,
        string $containingIdentifierProperty,
        string $expireDateModifier
    )
    {
        $this->sagaClass                    = $sagaClass;
        $this->identifierClass              = $identifierClass;
        $this->containingIdentifierProperty = $containingIdentifierProperty;
        $this->expireDateModifier           = $expireDateModifier;
    }

    /**
     * Receive saga class
     *
     * @return string
     */
    public function sagaClass(): string
    {
        return $this->sagaClass;
    }

    /**
     * Receive saga expire date modifier
     *
     * @return string
     */
    public function expireDateModifier(): string
    {
        return $this->expireDateModifier;
    }

    /**
     * Receive identifier class
     *
     * @return string
     */
    public function identifierClass(): string
    {
        return $this->identifierClass;
    }

    /**
     * Receive the name of the event property that contains the saga ID
     *
     * @return string
     */
    public function containingIdentifierProperty(): string
    {
        return $this->containingIdentifierProperty;
    }
}
