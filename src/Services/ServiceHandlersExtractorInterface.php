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

/**
 * Extract message/error handlers from service
 */
interface ServiceHandlersExtractorInterface
{
    public const HANDLER_TYPE_ERRORS = 'errors';
    public const HANDLER_TYPE_MESSAGES = 'messages';

    /**
     * Extract handlers data
     *
     * [
     *      self::HANDLER_TYPE_MESSAGES => object of Desperado\CQRS\Handlers\MessageHandlersCollection,
     *      self::HANDLER_TYPE_ERRORS   => object of Desperado\CQRS\Handlers\ExceptionHandlersCollection
     * ]
     *
     * @param ServiceInterface $service
     * @param string|null      $defaultServiceLoggerChannel
     *
     * @return array
     *
     * @throws \Desperado\ServiceBus\Services\Exceptions\ServiceConfigurationExceptionInterface
     */
    public function extractHandlers(
        ServiceInterface $service,
        string $defaultServiceLoggerChannel = null
    ): array;

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
