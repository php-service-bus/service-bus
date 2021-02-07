<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Stepan Zolotarev <zsl88.logging@gmail.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Context;

use ServiceBus\Common\Context\IncomingMessageMetadata;
use ServiceBus\Endpoint\Options\DeliveryOptionsFactory;
use Psr\Log\LoggerInterface;
use ServiceBus\Common\Context\ServiceBusContext;
use ServiceBus\Endpoint\EndpointRouter;

/**
 *
 */
final class KernelContextFactory implements ContextFactory
{
    /**
     * @var EndpointRouter
     */
    private $endpointRouter;

    /**
     * @var DeliveryOptionsFactory
     */
    private $optionsFactory;

    /**
     * @var LoggerInterface
     */
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

    public function create(object $message, array $headers, IncomingMessageMetadata $metadata): ServiceBusContext
    {
        return new KernelContext(
            message: $message,
            headers: $headers,
            metadata: $metadata,
            endpointRouter: $this->endpointRouter,
            optionsFactory: $this->optionsFactory,
            logger: new DefaultContextLogger(
                logger: $this->logger,
                message: $message,
                metadata: $metadata
            )
        );
    }
}
