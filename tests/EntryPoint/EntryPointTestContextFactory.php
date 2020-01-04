<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\EntryPoint;

use Psr\Log\LoggerInterface;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Context\ContextFactory;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 *
 */
final class EntryPointTestContextFactory implements ContextFactory
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create(IncomingPackage $package, object $message): ServiceBusContext
    {
        return new EntryPointTestContext($this->logger);
    }
}
