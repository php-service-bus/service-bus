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

namespace Desperado\ConcurrencyFramework\Domain\EventSourced;

use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;

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
