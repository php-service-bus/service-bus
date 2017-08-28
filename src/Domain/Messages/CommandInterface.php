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

namespace Desperado\ConcurrencyFramework\Domain\Messages;

/**
 * Used to request that an action should be taken. A Command is intended to be sent to a receiver (all commands should
 * have one logical owner and should be sent to the endpoint responsible for processing). As such, commands:
 *
 * - Are not allowed to be published.
 * - Cannot be subscribed to or unsubscribed from.
 * - Cannot implement \Desperado\Contract\Interfaces\EventInterface
 */
interface CommandInterface extends MessageInterface
{

}
