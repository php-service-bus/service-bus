<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Services\Stabs;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\Messages\HttpMessageInterface;
use Psr\Http\Message\RequestInterface;

/**
 *
 */
class TestServiceHttpCommand extends TestServiceCommand implements HttpMessageInterface
{
    /**
     * @inheritdoc
     */
    public static function fromRequest(RequestInterface $request): AbstractMessage
    {

    }
}
