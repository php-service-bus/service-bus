<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\Endpoint;

/**
 * Outbound message routing.
 */
final class EndpointRouter
{
    /**
     * Endpoints to which messages will be sent.
     *
     * name => [destination handler]
     *
     * @psalm-var array<string, array<mixed, \ServiceBus\Endpoint\Endpoint>>
     *
     * @var \ServiceBus\Endpoint\Endpoint[][]
     */
    private $routes = [];

    /**
     * Destination points for global routes (marked with "*").
     *
     * @psalm-var array<array-key, \ServiceBus\Endpoint\Endpoint>
     *
     * @var \ServiceBus\Endpoint\Endpoint[]
     */
    private $globalEndpoints = [];

    /**
     * @param Endpoint $defaultEndpoint
     */
    public function __construct(Endpoint $defaultEndpoint)
    {
        $this->addGlobalDestination($defaultEndpoint);
    }

    /**
     * Adding global delivery route.
     *
     * @param Endpoint $endpoint
     *
     * @return void
     */
    public function addGlobalDestination(Endpoint $endpoint): void
    {
        $this->globalEndpoints[\spl_object_hash($endpoint)] = $endpoint;
    }

    /**
     * Add custom endpoint for multiple messages.
     *
     * @psalm-param array<array-key, class-string>    $messages
     *
     * @param string[] $messages
     * @param Endpoint $endpoint
     *
     * @return void
     */
    public function registerRoutes(array $messages, Endpoint $endpoint): void
    {
        foreach ($messages as $message)
        {
            $this->registerRoute($message, $endpoint);
        }
    }

    /**
     * Add custom endpoint to specified message.
     *
     * @psalm-param class-string $messageClass
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
     * @psalm-return array<array-key, \ServiceBus\Endpoint\Endpoint>
     *
     * @param string $messageClass
     *
     * @return \ServiceBus\Endpoint\Endpoint[]
     */
    public function route(string $messageClass): array
    {
        if (false === empty($this->routes[$messageClass]))
        {
            return $this->routes[$messageClass];
        }

        return $this->globalEndpoints;
    }
}
