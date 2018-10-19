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

/**
 * Saga listener marker
 *
 * @Annotation
 * @Target("METHOD")
 */
final class SagaEventListener implements SagaAnnotationMarker
{
    /**
     * The event property that contains the saga ID
     * In the context of executing the handler, it overrides the value set for the saga globally
     *
     * @var string|null
     */
    private $containingIdProperty;

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
     * Receive event property that contains the saga ID
     *
     * @return string|null
     */
    public function containingIdProperty(): ?string
    {
        return $this->containingIdProperty;
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
