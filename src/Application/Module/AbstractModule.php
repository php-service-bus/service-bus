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

use Desperado\ConcurrencyFramework\Domain\Service\ServiceInterface;
use Desperado\ConcurrencyFramework\Infrastructure\Bridge\Annotation\AnnotationReader;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\MessageBus\MessageBusBuilder;

/**
 * Load modules
 */
abstract class AbstractModule
{
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
        foreach($this->getServices() as $service)
        {
            $this->configureService($service, $messageBusBuilder, $annotationsReader);
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
     * Configure service handlers
     *
     * @param ServiceInterface  $service
     * @param MessageBusBuilder $messageBusBuilder
     * @param AnnotationReader  $annotationsReader
     *
     * @return void
     */
    private function configureService(
        ServiceInterface $service,
        MessageBusBuilder $messageBusBuilder,
        AnnotationReader $annotationsReader
    ): void
    {

    }
}
