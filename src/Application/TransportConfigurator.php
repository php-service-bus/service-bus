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

namespace Desperado\ServiceBus\Application;

use Desperado\ServiceBus\OutboundMessage\Destination;
use Desperado\ServiceBus\OutboundMessage\OutboundMessageRoutes;
use Desperado\ServiceBus\Transport\Queue;
use Desperado\ServiceBus\Transport\QueueBind;
use Desperado\ServiceBus\Transport\Topic;
use Desperado\ServiceBus\Transport\Transport;

/**
 * Transport configuration for starting a subscription
 */
final class TransportConfigurator
{
    /**
     * @var Transport
     */
    private $transport;

    /**
     * Routes to which messages will be sent (Directions are indicated in the context of the current transport)
     *
     * @var OutboundMessageRoutes
     */
    private $outboundMessagesRoutes;

    /**
     * @param Transport             $transport
     * @param OutboundMessageRoutes $outboundMessagesRoutes
     */
    public function __construct(Transport $transport, OutboundMessageRoutes $outboundMessagesRoutes)
    {
        $this->transport              = $transport;
        $this->outboundMessagesRoutes = $outboundMessagesRoutes;
    }

    /**
     * Configure default destinations
     * In them, messages will be sent regardless of the availability of individual routes
     *
     * @noinspection PhpDocSignatureInspection
     *
     * @param Destination ...$destinations
     *
     * @return $this
     *
     */
    public function addDefaultDestinations(Destination ...$destinations): self
    {
        $this->outboundMessagesRoutes->addDefaultRoutes($destinations);

        return $this;
    }

    /**
     * Add directions for a specific message (in addition to the default directions)
     *
     * @noinspection PhpDocSignatureInspection
     *
     * @param string      $message
     * @param Destination ...$destinations
     *
     * @return $this
     *
     */
    public function registerCustomMessageDestinations(string $message, Destination ...$destinations): self
    {
        foreach($destinations as $destination)
        {
            $this->outboundMessagesRoutes->addRoute($message, $destination);
        }

        return $this;
    }

    /**
     * @param Queue          $queue
     * @param QueueBind|null $bindTo
     *
     * @return $this
     */
    public function addQueue(Queue $queue, ?QueueBind $bindTo = null): self
    {
        if(null !== $bindTo)
        {
            $this->transport->createTopic($bindTo->topic());
        }

        $this->transport->createQueue($queue, $bindTo);

        return $this;
    }

    /**
     * @param Topic $topic
     *
     * @return $this
     */
    public function createTopic(Topic $topic): self
    {
        $this->transport->createTopic($topic);

        return $this;
    }
}