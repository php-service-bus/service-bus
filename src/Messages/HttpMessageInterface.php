<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Messages;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Domain\Message\AbstractQuery;
use Psr\Http\Message\RequestInterface;

/**
 * The interface of the message received as a http request
 *
 * Can be applied ONLY to commands and queries
 */
interface HttpMessageInterface
{
    /**
     * Create command/query from http request instance
     *
     * @param RequestInterface $request
     *
     * @return AbstractCommand|AbstractQuery
     */
    public static function fromRequest(RequestInterface $request): AbstractMessage;
}
