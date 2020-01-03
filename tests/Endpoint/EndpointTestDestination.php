<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Tests\Endpoint;

use ServiceBus\Transport\Common\DeliveryDestination;

/**
 *
 */
final class EndpointTestDestination implements DeliveryDestination
{
    /** @var string */
    public $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }
}