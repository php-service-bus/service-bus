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

namespace Desperado\ServiceBus\Marshal\Serializer;

/**
 * Serialize array data
 */
interface ArraySerializer
{
    /**
     * Serialize specified data
     *
     * @param array<string, mixed> $data
     *
     * @return string
     *
     * @throws \Desperado\ServiceBus\Marshal\Serializer\Exceptions\SerializationFailed
     */
    public function serialize(array $data): string;

    /**
     * Unserialize data
     *
     * @param string $payload
     *
     * @return array<string, mixed>
     *
     * @throws \Desperado\ServiceBus\Marshal\Serializer\Exceptions\DeserializationFailed
     */
    public function unserialize(string $payload): array;
}
