<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Sagas\Configuration;

/**
 * Retrieving a list of saga event handlers and saga metadata
 */
interface SagaConfigurationLoader
{
    /**
     * Retrieving a list of saga event handlers
     *
     * @param string $sagaClass
     *
     * @return SagaConfiguration
     *
     * @throws \Desperado\ServiceBus\Sagas\Configuration\Exceptions\InvalidSagaConfiguration
     */
    public function load(string $sagaClass): SagaConfiguration;
}
