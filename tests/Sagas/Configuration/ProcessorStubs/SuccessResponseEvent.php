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

namespace Desperado\ServiceBus\Tests\Sagas\Configuration\ProcessorStubs;

use Desperado\ServiceBus\Common\Contract\Messages\Event;

/**
 *
 */
final class SuccessResponseEvent implements Event
{
    /** @var string|null */
    private $requestId;

    /**
     * @param string|null $requestId
     */
    public function __construct(?string $requestId = null)
    {
        $this->requestId = $requestId;
    }

    /**
     * @return string|null
     */
    public function requestId(): ?string
    {
        return $this->requestId;
    }
}
