<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Infrastructure\Alerting;

/**
 * Message sent context
 *
 * @psalm-immutable
 */
final class AlertContext
{
    /**
     * Is it necessary to somehow highlight the message
     *
     * @var bool
     */
    public $toDrawAttention;

    /**
     * Description of destination (channel, chat, etc.)
     *
     * @var string|int|null
     */
    public $toTopic;

    /**
     * @param int|string|null $toTopic
     */
    public function __construct(bool $toDrawAttention = false, $toTopic = null)
    {
        $this->toDrawAttention = $toDrawAttention;
        $this->toTopic         = $toTopic;
    }
}
