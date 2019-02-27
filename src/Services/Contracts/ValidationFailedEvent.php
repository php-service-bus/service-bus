<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Services\Contracts;

/**
 *
 */
interface ValidationFailedEvent
{
    /**
     * List of validate violations:.
     *
     * [
     *    'propertyPath' => [
     *        0 => 'some message',
     *        ....
     *    ]
     * ]
     *
     * @psalm-param array<string, array<int, string>> $violations
     *
     * @param string $correlationId
     * @param array  $violations
     *
     * @return self
     */
    public static function create(string $correlationId, array $violations): self;

    /**
     * Receive request correlation id (Message trace id).
     *
     * @return string
     */
    public function correlationId(): string;

    /**
     * Receive list of validate violations.
     *
     * @psalm-return array<string, array<int, string>>
     *
     * @return array
     */
    public function violations(): array;
}
