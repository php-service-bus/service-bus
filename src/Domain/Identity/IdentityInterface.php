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

namespace Desperado\ConcurrencyFramework\Domain\Identity;

/**
 * Identity
 */
interface IdentityInterface
{
    /**
     * Get ID as string
     *
     * @return string
     */
    public function toString(): string;

    /**
     * Object to string ID representation
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Get identity as composite key (class:identity)
     *
     * @return string
     */
    public function toCompositeIndex(): string;
}
