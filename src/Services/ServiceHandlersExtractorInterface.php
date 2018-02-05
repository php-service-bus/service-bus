<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services;

use Desperado\ServiceBus\Services\Handlers\MessageHandlersCollection;

/**
 * Extract message/error handlers from service
 */
interface ServiceHandlersExtractorInterface
{
    /**
     * Extract handlers data
     *
     * @param ServiceInterface $service
     * @param string|null      $defaultServiceLoggerChannel
     *
     * @return MessageHandlersCollection
     *
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    public function extractHandlers(
        ServiceInterface $service,
        string $defaultServiceLoggerChannel = null
    ): MessageHandlersCollection;

    /**
     * Extract service logger channel
     *
     * @param ServiceInterface $service
     *
     * @return string
     *
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    public function extractServiceLoggerChannel(ServiceInterface $service): string;
}
