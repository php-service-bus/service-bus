<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Configuration;

/**
 * Saga configuration extractor
 */
interface SagaConfigurationExtractorInterface
{
    /**
     * Extract saga configuration
     *
     * @param string $sagaNamespace
     *
     * @return SagaConfiguration
     *
     * @throws \Desperado\ServiceBus\Saga\Configuration\Exceptions\SagaConfigurationException
     */
    public function extractSagaConfiguration(string $sagaNamespace): SagaConfiguration;

    /**
     * Extract saga event listeners
     *
     * @param string $sagaNamespace
     *
     * @return SagaListenerConfiguration[]
     *
     * @throws \Desperado\ServiceBus\Saga\Configuration\Exceptions\SagaConfigurationException
     */
    public function extractSagaListeners(string $sagaNamespace): array;
}
