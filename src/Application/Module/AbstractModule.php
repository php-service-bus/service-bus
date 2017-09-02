<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Application\Module;

use Desperado\ConcurrencyFramework\Application\Service\ServiceHandlersSetup;
use Desperado\ConcurrencyFramework\Domain\Service\ServiceInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Logger\LoggerRegistry;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\MessageBus\MessageBusBuilder;
use Psr\Log\LoggerInterface;

/**
 * Load modules
 */
abstract class AbstractModule
{
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? LoggerRegistry::getLogger('modules');
    }

    /**
     * Boot module
     *
     * @param MessageBusBuilder $messageBusBuilder
     * @param AnnotationReader  $annotationsReader
     *
     * @return void
     */
    public function boot(MessageBusBuilder $messageBusBuilder, AnnotationReader $annotationsReader): void
    {
        $serviceSetup = new ServiceHandlersSetup($messageBusBuilder, $annotationsReader, $this->logger);

        foreach($this->getServices() as $service)
        {
            $serviceSetup->setup($service);
        }
    }

    /**
     * Get module services
     *
     * @return ServiceInterface[]
     */
    protected function getServices(): array
    {
        return [];
    }

    /**
     * Get logger
     *
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
