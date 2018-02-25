<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Transport\RabbitMqTransport;

use Bunny\Channel;

/**
 * Created channel data
 */
final class RabbitMqChannelData
{
    /**
     * Channel
     *
     * @var Channel
     */
    private $channel;

    /**
     * Queue name
     *
     * @var string
     */
    private $queue;

    /**
     * Create new channel data
     *
     * @param Channel $channel
     * @param string  $queue
     *
     * @return self
     */
    public static function create(Channel $channel, string $queue): self
    {
        $self = new self();

        $self->channel = $channel;
        $self->queue = $queue;

        return $self;
    }

    /**
     * Get channel instance
     *
     * @return Channel
     */
    public function getChannel(): Channel
    {
        return $this->channel;
    }

    /**
     * Get queue name
     *
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
