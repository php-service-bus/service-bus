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

namespace Desperado\ServiceBus\Services\Contracts;

use Desperado\ServiceBus\Common\Contract\Messages\Event;

/**
 *
 */
interface ValidationFailedEvent extends Event
{
    /**
     * List of validate violations:
     *
     * [
     *    'propertyPath' => [
     *        0 => 'some message',
     *        ....
     *    ]
     * ]
     *
     * @param string                            $correlationId
     * @param array<string, array<int, string>> $violations
     *
     * @return self
     */
    public static function create(string $correlationId, array $violations): self;

    /**
     * Receive request correlation id (Message trace id)
     *
     * @return string
     */
    public function correlationId(): string;

    /**
     * Receive list of validate violations
     *
     * @return array<string, array<int, string>>
     */
    public function violations(): array;
}
