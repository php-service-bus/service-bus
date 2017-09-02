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

namespace Desperado\Framework\Domain\EventSourced;

use Desperado\Framework\Domain\Messages\CommandInterface;

/**
 * Aggregate root
 */
interface AggregateRootInterface
{
    /**
     * Create aggregate
     *
     * @param CommandInterface $command
     *
     * @return $this
     */
    public static function create(CommandInterface $command);
}
