<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests;

use Desperado\Domain\Transport\Context\OutboundMessageContextInterface;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 *
 */
class TestApplicationContext extends AbstractExecutionContext
{
    /**
     * @inheritdoc
     */
    public function getLogger(string $channelName): LoggerInterface
    {
        return new NullLogger();
    }

    /**
     * @inheritdoc
     */
    public function getOutboundMessageContext(): ?OutboundMessageContextInterface
    {
        return new TestOutboundMessageContext();
    }

    /**
     * @inheritdoc
     */
    public function applyOutboundMessageContext(OutboundMessageContextInterface $outboundMessageContext)
    {

    }
}
