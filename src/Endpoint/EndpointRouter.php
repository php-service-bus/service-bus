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

/**
 * Outbound message routing
 */
final class EndpointRouter
{
    /**
     * Endpoints to which messages will be sent
     *
     * name => [destination handler]
     *
     * @var array<string, array<mixed, \ServiceBus\Endpoint\Endpoint>>
     */
    private $routes = [];

    /**
     * Default end point is the application itself (sending goes to the same transport from which messages are received)
     *
     * @var  Endpoint
     */
    private $defaultEndpoint;

    /**
     * @param Endpoint $defaultEndpoint
     */
    public function __construct(Endpoint $defaultEndpoint)
    {
        $this->defaultEndpoint = $defaultEndpoint;
    }

    /**
     * Add custom endpoint to specified message
     *
     * @param string   $messageClass
     * @param Endpoint $endpoint
     *
     * @return void
     */
    public function registerRoute(string $messageClass, Endpoint $endpoint): void
    {
        $this->routes[$messageClass][] = $endpoint;
    }

    /**
     * Receiving a message sending route
     * If no specific route is registered, the default endpoint route will be returned.
     *
     * @param string $messageClass
     *
     * @return array<mixed, \ServiceBus\Endpoint\Endpoint>
     */
    public function route(string $messageClass): array
    {
        return $this->routes[$messageClass] ?? [$this->defaultEndpoint];
    }
}
