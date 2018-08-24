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
use Desperado\ServiceBus\Transport\TopicBind;
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
     * Configure default routes for messages
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
     * Add routes for specific messages
     * If the message has its own route, it will not be sent to the default route
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
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     * @throws \Desperado\ServiceBus\Transport\Exceptions\BindFailed
     */
    public function addQueue(Queue $queue, ?QueueBind $bindTo = null): self
    {
        $this->transport->createQueue($queue);

        if(null !== $bindTo)
        {
            $this->bindQueue($bindTo);
        }

        return $this;
    }

    /**
     * @param Topic          $topic
     * @param TopicBind|null $bindTo
     *
     * @return $this
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     * @throws \Desperado\ServiceBus\Transport\Exceptions\BindFailed
     */
    public function createTopic(Topic $topic, ?TopicBind $bindTo = null): self
    {
        $this->transport->createTopic($topic);

        if(null !== $bindTo)
        {
            $this->bindTopic($bindTo);
        }

        return $this;
    }

    /**
     * @param TopicBind $bindTo
     *
     * @return $this
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     * @throws \Desperado\ServiceBus\Transport\Exceptions\BindFailed
     */
    public function bindTopic(TopicBind $bindTo): self
    {
        $this->transport->bindTopic($bindTo);

        return $this;
    }

    /**
     * @param QueueBind $bindTo
     *
     * @return $this
     *
     * @throws \Desperado\ServiceBus\Transport\Exceptions\ConnectionFail
     * @throws \Desperado\ServiceBus\Transport\Exceptions\BindFailed
     */
    public function bindQueue(QueueBind $bindTo): self
    {
        $this->transport->bindQueue($bindTo);

        return $this;

    }
}