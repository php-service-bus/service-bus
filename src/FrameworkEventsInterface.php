<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework;

/**
 * Framework core events
 */
interface FrameworkEventsInterface
{
    public const BEFORE_MESSAGE_EXECUTION = 'message.before';

    public const AFTER_MESSAGE_EXECUTION = 'message.after';

    public const MESSAGE_EXECUTION_FAILED = 'message.failed';

    public const BEFORE_FLUSH_EXECUTION = 'flush.before';

    public const AFTER_FLUSH_EXECUTION = 'flush.after';

    public const FLUSH_EXECUTION_FAILED = 'flush.failed';
}
