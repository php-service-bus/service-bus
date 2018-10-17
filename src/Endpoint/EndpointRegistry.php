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

use Desperado\ServiceBus\Endpoint\Exceptions\NotRegisteredEndpoint;

/**
 * List of all endpoints to send messages
 */
final class EndpointRegistry
{
    /**
     * Endpoints to which messages will be sent
     *
     * name => destination handler
     *
     * @var array<string, \Desperado\ServiceBus\Endpoint\Endpoint>
     */
    private $endpoints = [];

    /**
     * @param Endpoint $defaultEndpoint
     */
    public function __construct(Endpoint $defaultEndpoint)
    {
        $this->add($defaultEndpoint);
    }

    /**
     * Adding a new endpoint to send messages
     *
     * @param Endpoint $endpoint
     *
     * @return void
     */
    public function add(Endpoint $endpoint): void
    {
        $this->endpoints[$endpoint->name()] = $endpoint;
    }

    /**
     * Extract endpoint via name
     *
     * @param string $name
     *
     * @return Endpoint
     *
     * @throws \Desperado\ServiceBus\Endpoint\Exceptions\NotRegisteredEndpoint Endpoint is not registered
     */
    public function extract(string $name): Endpoint
    {
        if(true === isset($this->endpoints[$name]))
        {
            return $this->endpoints[$name];
        }

        throw new NotRegisteredEndpoint(
            \sprintf('Could not find endpoint "%s"', $name)
        );
    }
}
