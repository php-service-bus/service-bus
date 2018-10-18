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

namespace Desperado\ServiceBus\Infrastructure\Transport\Implementation\Amqp;

/**
 * Quality Of Service settings
 */
final class AmqpQoSConfiguration
{
    private const DEFAULT_QOS_PRE_FETCH_SIZE  = 0;
    private const DEFAULT_QOS_PRE_FETCH_COUNT = 100;
    private const DEFAULT_QOS_GLOBAL          = false;

    /**
     * The client can request that messages be sent in advance so that when the client finishes processing a message,
     * the following message is already held locally, rather than needing to be sent down the channel. Prefetching
     * gives a performance improvement. This field specifies the prefetch window size in octets. The server will send
     * a message in advance if it is equal to or smaller in size than the available prefetch size (and also falls into
     * other prefetch limits). May be set to zero, meaning "no specific limit", although other prefetch limits may
     * still apply. The prefetch-size is ignored if the no-ack option is set.
     *
     * The server MUST ignore this setting when the client is not processing any messages - i.e. the prefetch size does
     * not limit the transfer of single messages to a client, only the sending in advance of more messages while the
     * client still has one or more unacknowledged messages.
     *
     * @var int
     */
    private $size;

    /**
     * Specifies a prefetch window in terms of whole messages. This field may be used in combination with the
     * prefetch-size field; a message will only be sent in advance if both prefetch windows (and those at the channel
     * and connection level) allow it. The prefetch-count is ignored if the no-ack option is set.
     *
     * The server may send less data in advance than allowed by the client's specified prefetch windows but it MUST NOT
     * send more.
     *
     * @var int
     */
    private $count;

    /**
     * RabbitMQ has reinterpreted this field. The original specification said: "By default the QoS settings apply to
     * the current channel only. If this field is set, they are applied to the entire connection." Instead, RabbitMQ
     * takes global=false to mean that the QoS settings should apply per-consumer (for new consumers on the channel;
     * existing ones being unaffected) and global=true to mean that the QoS settings should apply per-channel.
     *
     * @var bool
     */
    private $global;

    /**
     * @param int  $size
     * @param int  $count
     * @param bool $global
     */
    public function __construct(
        int $size = self::DEFAULT_QOS_PRE_FETCH_SIZE,
        int $count = self::DEFAULT_QOS_PRE_FETCH_COUNT,
        bool $global = self::DEFAULT_QOS_GLOBAL
    )
    {
        $this->size   = $size;
        $this->count  = $count;
        $this->global = $global;
    }

    /**
     * @return int
     */
    public function qosSize(): int
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function qosCount(): int
    {
        return $this->count;
    }

    /**
     * @return bool
     */
    public function isGlobal(): bool
    {
        return $this->global;
    }
}
