<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=1);

namespace ServiceBus\Tests\EntryPoint;

/**
 *
 */
final class EntryPointTestMessage implements \JsonSerializable
{
    /**
     * @var string
     */
    public $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function jsonSerialize(): array
    {
        return ['id' => $this->id];
    }
}
