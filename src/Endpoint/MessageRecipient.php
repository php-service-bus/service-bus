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

namespace Desperado\ServiceBus\Endpoint;

/**
 * Point to which the message will be sent
 */
final class MessageRecipient
{
    /**
     * Destination handler name
     *
     * @var string
     */
    private $endpointName;

    /**
     * Specific path in the context of transport
     *
     * @var TransportLevelDestination
     */
    private $transportDestination;

    /**
     * @param string                    $endpointName
     * @param TransportLevelDestination $transportDestination
     */
    public function __construct(string $endpointName, TransportLevelDestination $transportDestination)
    {
        $this->endpointName         = $endpointName;
        $this->transportDestination = $transportDestination;
    }

    /**
     * @return string
     */
    public function endpointName(): string
    {
        return $this->endpointName;
    }

    /**
     * @return TransportLevelDestination
     */
    public function transportDestination(): TransportLevelDestination
    {
        return $this->transportDestination;
    }
}
