<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Annotations\Services;

/**
 * Handler can be called via the http request (and not only from the transport bus, for example, a rabbitMq. The
 * command/query can still be called using the transport bus).
 *
 * The command/query should implement the interface "\Desperado\ServiceBus\Messages\HttpMessageInterface"
 *
 * To support working with the http entry point, you must specify the `route` and `method`
 */
interface HttpHandlerAnnotationInterface
{
    /**
     * Get http request route
     *
     * @return string|null
     */
    public function getRoute(): ?string;

    /**
     * Get http request method
     *
     * @return string|null
     */
    public function getMethod(): ?string;
}
