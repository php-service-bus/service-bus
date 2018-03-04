<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\HttpServer\Context;

use Desperado\ServiceBus\HttpServer\HttpResponse;

/**
 * Outbound http context
 */
interface OutboundHttpContextInterface
{
    /**
     * The message was received on the https scheme, need to return a response
     *
     * @return bool
     */
    public function httpSessionStarted(): bool;

    /**
     * Set the reply http response
     *
     * @param HttpResponse $response
     *
     * @return void
     */
    public function bindResponse(HttpResponse $response): void;

    /**
     * Response object specified
     *
     * @return bool
     */
    public function responseBind(): bool;

    /**
     * Get http response
     *
     * @return HttpResponse|null
     */
    public function getResponseData(): ?HttpResponse;
}
