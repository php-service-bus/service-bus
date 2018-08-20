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

namespace Desperado\ServiceBus\Tests\Sagas\Configuration\ConfigurationStubs;

use Desperado\ServiceBus\Common\Contract\Messages\Event;

/**
 *
 */
final class SomeAnotherSagaEvent implements Event
{
    /**
     * @var string
     */
    private $requestId;

    /**
     * @param string $requestId
     */
    public function __construct(string $requestId)
    {
        /** @noinspection UnusedConstructorDependenciesInspection */
        $this->requestId = $requestId;
    }
}
