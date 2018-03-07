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
 * Saga listener marker
 *
 * @Annotation
 * @Target("METHOD")
 */
final class SagaEventListener extends AbstractAnnotation
{
    /**
     * The event property that contains the saga ID
     *
     * @var string|null
     */
    protected $containingIdentifierProperty;

    /**
     * Get the event property that contains the saga ID
     *
     * @return string|null
     */
    public function getContainingIdentifierProperty(): ?string
    {
        return $this->containingIdentifierProperty;
    }
}
