<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\EmailNotifications\Event;

use Desperado\Domain\Message\AbstractEvent;

/**
 * Mail successfully sent
 *
 * @see SendEmailCommand
 */
class EmailSentEvent extends AbstractEvent
{
    /**
     * Operation identifier
     *
     * @var string
     */
    protected $requestId;

    /**
     * Get operation identifier
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
