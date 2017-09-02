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

namespace Desperado\Framework\Domain\Messages;

/**
 * Used to communicate that some action has taken place. An Event should be published. An event:
 *
 * - Can be subscribed to and unsubscribed from.
 * - Cannot be sent using `send` (since all events should be published).
 * - Cannot implement \Desperado\Contract\Interfaces\Messages\CommandInterface
 */
interface EventInterface extends MessageInterface
{

}
