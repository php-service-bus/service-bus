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
     * Class namespace
     *
     * @var string
     */
    public $sagaClass;

    /**
     * Identifier class
     *
     * @var string
     */
    public $identifierClass;

    /**
     * The field that contains the saga identifier
     *
     * @var string
     */
    public $containingIdentifierProperty;

    /**
     * Saga expire date modifier
     *
     * @see http://php.net/manual/ru/datetime.formats.relative.php
     *
     * @var string
     */
    public $expireDateModifier;

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
}
