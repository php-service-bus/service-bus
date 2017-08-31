<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Annotation;

use Desperado\ConcurrencyFramework\Domain\Annotation\AbstractAnnotation;

/**
 * Saga listener marker
 *
 * @Annotation
 * @Target("METHOD")
 */
class SagaListener extends AbstractAnnotation
{
    /**
     * The event property that contains the saga ID
     *
     * @var string
     */
    public $containingIdentityProperty;

    /**
     * Saga identity class namespace
     *
     * @var string
     */
    public $identityNamespace;
}
