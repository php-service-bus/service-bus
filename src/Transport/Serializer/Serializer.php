<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\Serializer;

/**
 * Serialize/unserialize data
 */
interface Serializer
{
    /**
     * Serialize specified data
     *
     * @param array<string, mixed> $data
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function serialize(array $data): string;

    /**
     * Unserialize received data
     *
     * @param string $payload
     *
     * @return array<string, mixed>
     *
     * @throws \RuntimeException
     */
    public function unserialize(string $payload): array;
}
