<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations\Sagas;

use Desperado\Domain\Annotations\AbstractAnnotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class Saga extends AbstractAnnotation
{
    /**
     * Saga identifier class namespace
     *
     * @var string|null
     */
    public $identifierNamespace;

    /**
     * The event property that contains the saga ID
     *
     * @var string|null
     */
    public $containingIdentifierProperty;

    /**
     * Saga expire date modifier
     *
     * @see http://php.net/manual/ru/datetime.formats.relative.php
     *
     * @var string
     */
    public $expireDateModifier = '+1 hour';
}
