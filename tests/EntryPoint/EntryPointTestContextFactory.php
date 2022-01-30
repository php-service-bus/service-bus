<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\EntryPoint;

use Psr\Log\LoggerInterface;
use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Context\ContextFactory;

/**
 *
 */
final class EntryPointTestContextFactory implements ContextFactory
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create(object $message, array $headers, IncomingMessageMetadata $metadata): ServiceBusContext
    {
        return new EntryPointTestContext($this->logger, $message, $metadata);
    }
}
