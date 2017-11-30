<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Backend\ReactRabbitMq;

use Bunny\Channel;

/**
 * Created channel data
 */
class ReactRabbitMqChannelData
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
     * @param Channel $channel
     * @param string  $queue
     */
    public function __construct(Channel $channel, string $queue)
    {
        $this->channel = $channel;
        $this->queue = $queue;
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
}
