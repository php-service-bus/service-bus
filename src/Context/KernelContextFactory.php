<?php
/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Context;

use ServiceBus\Endpoint\Options\DeliveryOptionsFactory;
use Psr\Log\LoggerInterface;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Endpoint\EndpointRouter;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 *
 */
final class KernelContextFactory implements ContextFactory
{
    /** @var EndpointRouter */
    private $endpointRouter;

    /** @var DeliveryOptionsFactory */
    private $optionsFactory;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        EndpointRouter $endpointRouter,
        DeliveryOptionsFactory $optionsFactory,
        LoggerInterface $logger
    ) {
        $this->endpointRouter = $endpointRouter;
        $this->optionsFactory = $optionsFactory;
        $this->logger         = $logger;
    }

    public function create(IncomingPackage $package, object $message): ServiceBusContext
    {
        return new KernelContext($package, $message, $this->endpointRouter, $this->optionsFactory, $this->logger);
    }
}
