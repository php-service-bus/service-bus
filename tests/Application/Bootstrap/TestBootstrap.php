<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\Application\Bootstrap;

use Desperado\ServiceBus\Application\Bootstrap\AbstractBootstrap;
use Desperado\ServiceBus\Application\Bootstrap\BootstrapContainerConfiguration;
use Desperado\ServiceBus\Application\Bootstrap\BootstrapServicesDefinitions;

/**
 *
 */
class TestBootstrap extends AbstractBootstrap
{
    /**
     * @inheritdoc
     */
    protected function getBootstrapContainerConfiguration(): BootstrapContainerConfiguration
    {
        return BootstrapContainerConfiguration::create();
    }

    /**
     * @inheritdoc
     */
    protected function getBootstrapServicesDefinitions(): BootstrapServicesDefinitions
    {
        return BootstrapServicesDefinitions::create(
            'messageTransportKey',
            'kernelKey',
            'sagaStorageKey',
            'schedulerStorageKey',
            'applicationContextKey'
        );
    }
}
