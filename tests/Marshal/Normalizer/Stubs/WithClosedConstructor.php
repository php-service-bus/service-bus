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
final class WithClosedConstructor
{
    /** @var string */
    private $key;

    /** @var string|null */
    private $someSecondKey;

    /**
     * @param string $key
     *
     * @return self
     */
    public static function create(string $key): self
    {
        $self      = new self();
        $self->key = $key;

        return $self;
    }

    private function __construct()
    {

    }
}