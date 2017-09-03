<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\EventSourcing\Annotation;

use Desperado\Framework\Domain\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Saga extends AbstractAnnotation
{
    /**
     * Saga identity class namespace
     *
     * @var string
     */
    public $identityNamespace;

    /**
     * The event property that contains the saga ID
     *
     * @var string
     */
    public $containingIdentityProperty;
}