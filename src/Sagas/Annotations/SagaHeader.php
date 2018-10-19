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

namespace Desperado\ServiceBus\Sagas\Annotations;

use Desperado\ServiceBus\Sagas\Configuration\SagaMetadata;

/**
 * @Annotation
 * @Target("CLASS")
 */
final class SagaHeader implements SagaAnnotationMarker
{
    /**
     * Saga identifier class
     *
     * @var string|null
     */
    private $idClass;

    /**
     * The event property that contains the saga ID
     *
     * @var string|null
     */
    private $containingIdProperty;

    /**
     * Saga expire date modifier
     *
     * @see http://php.net/manual/ru/datetime.formats.relative.php
     *
     * @var string|null
     */
    private $expireDateModifier = SagaMetadata::DEFAULT_EXPIRE_INTERVAL;

    /**
     * @param array<string, mixed> $data
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(array $data)
    {
        /** @var string|null $value */
        foreach($data as $key => $value)
        {
            if(false === \property_exists($this, $key))
            {
                throw new \InvalidArgumentException(
                    \sprintf('Unknown property "%s" on annotation "%s"', $key, \get_class($this))
                );
            }

            $this->{$key} = $value;
        }
    }

    /**
     * Receive saga identifier class
     *
     * @return string
     */
    public function idClass(): string
    {
        return (string) $this->idClass;
    }

    /**
     * Receive event property that contains the saga ID
     *
     * @return string
     */
    public function containingIdProperty(): string
    {
        return (string) $this->containingIdProperty;
    }

    /**
     * Receive saga expire date modifier
     *
     * @return string
     */
    public function expireDateModifier(): string
    {
        return true === $this->hasSpecifiedExpireDateModifier()
            ? (string) $this->expireDateModifier
            : SagaMetadata::DEFAULT_EXPIRE_INTERVAL;
    }

    /**
     * Has specified expire date interval
     *
     * @return bool
     */
    public function hasSpecifiedExpireDateModifier(): bool
    {
        return null !== $this->expireDateModifier && '' !== $this->expireDateModifier;
    }

    /**
     * Has specified saga identifier class
     *
     * @return bool
     */
    public function hasIdClass(): bool
    {
        return null !== $this->idClass && '' !== $this->idClass;
    }

    /**
     * Has specified event property that contains the saga ID
     *
     * @return bool
     */
    public function hasContainingIdProperty(): bool
    {
        return null !== $this->containingIdProperty && '' !== $this->containingIdProperty;
    }
}
