<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Metadata;

/**
 *
 */
interface ServiceBusMetadata
{
    public const SERVICE_BUS_TRACE_ID        = 'X-SERVICE-BUS-TRACE-ID';
    public const SERVICE_BUS_SERIALIZER_TYPE = 'X-SERVICE-BUS-ENCODER';
    public const SERVICE_BUS_MESSAGE_TYPE    = 'X-SERVICE-BUS-MESSAGE-TYPE';

    public const SERVICE_BUS_MESSAGE_FAILED_IN   = 'X-SERVICE-BUS-FAILED_IN';
    public const SERVICE_BUS_MESSAGE_RETRY_COUNT = 'X-SERVICE-BUS-RETRY-COUNT';

    public const INTERNAL_METADATA_KEYS = [
        self::SERVICE_BUS_TRACE_ID,
        self::SERVICE_BUS_SERIALIZER_TYPE,
        self::SERVICE_BUS_MESSAGE_TYPE,
        self::SERVICE_BUS_MESSAGE_RETRY_COUNT,
        self::SERVICE_BUS_MESSAGE_FAILED_IN,
    ];
}
