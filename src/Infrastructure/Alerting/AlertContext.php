<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 0);

namespace ServiceBus\Infrastructure\Alerting;

/**
 * Message sent context.
 *
 * @psalm-immutable
 */
final class AlertContext
{
    /**
     * Is it necessary to somehow highlight the message.
     *
     * @psalm-readonly
     *
     * @var bool
     */
    public $toDrawAttention;

    /**
     * Description of destination (channel, chat, etc.).
     *
     * @psalm-readonly
     *
     * @var string|int|null
     */
    public $toTopic;

    public function __construct(bool $toDrawAttention = false, int|string|null $toTopic = null)
    {
        $this->toDrawAttention = $toDrawAttention;
        $this->toTopic         = $toTopic;
    }
}
