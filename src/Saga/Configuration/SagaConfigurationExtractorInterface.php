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
     * [
     *     0 => 'SomeExpireDateModifier',
     *     1 => 'SomeIdentifierClassNamespace',
     *     2 => 'SomeContainingIdentifierProperty'
     * ]
     *
     * @param string $sagaNamespace
     *
     * @return array
     *
     * @throws \Desperado\ServiceBus\Saga\Configuration\Exceptions\SagaConfigurationException
     */
    public function extractSagaConfiguration(string $sagaNamespace): array;

    /**
     * Extract saga event listeners
     *
     * [
     *     0 => 'SomeEventNamespace',
     *     1 => 'SomeCustomContainingIdentifierProperty'
     * ]
     *
     * @param string $sagaNamespace
     *
     * @return array
     *
     * @throws \Desperado\ServiceBus\Saga\Configuration\Exceptions\SagaConfigurationException
     */
    public function extractSagaListeners(string $sagaNamespace): array;
}
