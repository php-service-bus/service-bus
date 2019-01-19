<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Contracts;

use ServiceBus\Common\Messages\Event;

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
