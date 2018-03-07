<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace Desperado\ServiceBus\Annotations\Sagas;

use Desperado\ServiceBus\Annotations\AbstractAnnotation;

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
    private $identifierNamespace;

    /**
     * The event property that contains the saga ID
     *
     * @var string|null
     */
    private $containingIdentifierProperty;

    /**
     * Saga expire date modifier
     *
     * @see http://php.net/manual/ru/datetime.formats.relative.php
     *
     * @var string
     */
    private $expireDateModifier = '+1 hour';

    /**
     * Get saga identifier class namespace
     *
     * @return string|null
     */
    public function getIdentifierNamespace(): ?string
    {
        return $this->identifierNamespace;
    }

    /**
     * Get the event property that contains the saga ID
     *
     * @return string|null
     */
    public function getContainingIdentifierProperty(): ?string
    {
        return $this->containingIdentifierProperty;
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
}
