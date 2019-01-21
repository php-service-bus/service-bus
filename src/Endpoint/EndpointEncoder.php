<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint;

use ServiceBus\MessageSerializer\MessageEncoder;
use ServiceBus\MessageSerializer\Symfony\SymfonyMessageSerializer;

/**
 * Endpoint message encoder
 *
 * @property-read string         $tag
 * @property-read MessageEncoder $handler
 */
final class EndpointEncoder
{
    private const DEFAULT_ENCODER = 'service_bus.encoder.default_handler';

    /**
     * @var string
     */
    public $tag;

    /**
     * @var MessageEncoder
     */
    public $handler;

    /**
     * @return self
     */
    public static function createDefault(): self
    {
        return new self(self::DEFAULT_ENCODER, new SymfonyMessageSerializer());
    }

    /**
     * @param string         $tag
     * @param MessageEncoder $handler
     */
    public function __construct(string $tag, MessageEncoder $handler)
    {
        $this->tag     = $tag;
        $this->handler = $handler;
    }
}
