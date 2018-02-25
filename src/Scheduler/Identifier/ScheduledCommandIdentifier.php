<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Scheduler\Identifier;

use Desperado\Domain\Identity\AbstractIdentity;
use Desperado\Domain\Uuid;

/**
 * Scheduled command identifier
 */
class ScheduledCommandIdentifier extends AbstractIdentity
{
    /**
     * Create new identifier
     *
     * @return self
     */
    public static function new(): self
    {
        return new self(Uuid::v4());
    }
}
