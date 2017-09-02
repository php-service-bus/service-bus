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

namespace Desperado\Framework\Domain;

use Ramsey\Uuid\Uuid as RamseyUuid;

/**
 * Class Uuid
 *
 * @package Desperado\Core\Domain
 */
final class Uuid
{
    /**
     * Generate a version 4 (random) UUID
     *
     * @return string
     */
    public static function new(): string
    {
        return RamseyUuid::uuid4()->toString();
    }

    /**
     * Check if a string is a valid UUID
     *
     * @param string $uuid
     *
     * @return bool
     */
    public static function isValid(string $uuid): bool
    {
        return RamseyUuid::isValid($uuid);
    }

    /**
     * Close constructor
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {

    }
}
