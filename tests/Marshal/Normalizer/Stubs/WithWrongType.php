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

namespace Desperado\ServiceBus\Tests\Marshal\Normalizer\Stubs;

/**
 *
 */
final class WithWrongType
{
    /**
     * @var string
     */
    private $property;

    /**
     * @param int $qwerty
     *
     * @return self
     */
    public static function create(int $qwerty): self
    {
        $self = new self();
        $self->property = $qwerty;

        return $self;
    }
}
