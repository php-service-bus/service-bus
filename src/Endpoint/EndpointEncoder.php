<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Endpoint;

use ServiceBus\MessageSerializer\ObjectSerializer;
use ServiceBus\MessageSerializer\Symfony\SymfonyJsonObjectSerializer;

/**
 * Endpoint message encoder.
 *
 * @psalm-immutable
 */
final class EndpointEncoder
{
    private const DEFAULT_ENCODER = 'service_bus.encoder.default_handler';

    /**
     * @psalm-readonly
     *
     * @var string
     */
    public $tag;

    /**
     * @psalm-readonly
     *
     * @var ObjectSerializer
     */
    public $handler;

    public static function createDefault(): self
    {
        return new self(
            tag: self::DEFAULT_ENCODER,
            handler: new SymfonyJsonObjectSerializer()
        );
    }

    public function __construct(string $tag, ObjectSerializer $handler)
    {
        $this->tag     = $tag;
        $this->handler = $handler;
    }
}
