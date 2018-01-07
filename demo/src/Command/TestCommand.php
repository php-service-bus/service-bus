<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\Command;

use Desperado\Domain\Message\AbstractCommand;

/**
 *
 */
class TestCommand extends AbstractCommand
{
    /**
     * Request ID
     *
     * @var string
     */
    protected $requestId;

    /**
     * Get request ID
     *
     * @return string
     */
    public function getRequestId(): string
    {
        return $this->requestId;
    }
}
