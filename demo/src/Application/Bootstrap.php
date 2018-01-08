<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Demo\Application;

use Desperado\ServiceBus\Application\AbstractBootstrap;
use Desperado\ServiceBus\Application\BootstrapContainerConfiguration;
use Desperado\ServiceBus\Application\BootstrapServicesDefinitions;
use Desperado\ServiceBus\Demo\Application\DependencyInjection\DemoExtension;

/**
 *
 */
class Bootstrap extends AbstractBootstrap
{

    /**
     * @inheritdoc
     */
    protected function getBootstrapServicesDefinitions(): BootstrapServicesDefinitions
    {
        return BootstrapServicesDefinitions::create(
            'message_transport.rabbit_mq',
            'application_kernel',
            'sagas_storage',
            'application_context'
        );
    }

    /**
     * @inheritdoc
     */
    protected function getBootstrapContainerConfiguration(): BootstrapContainerConfiguration
    {
        return BootstrapContainerConfiguration::create(
            [new DemoExtension()],
            ['transport_connection_dsn' => \getenv('TRANSPORT_CONNECTION_DSN')]
        );
    }
}
