<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation).
 *
 * @author  Maksim Masiukevich <contacts@desperado.dev>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types=0);

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
     * @psalm-var array<class-string, list<\ServiceBus\Endpoint\Endpoint>>
     *
     * @var \ServiceBus\Endpoint\Endpoint[][]
     */
    private $routes = [];

    /**
     * Destination points for global routes (marked with "*").
     *
     * @psalm-var array<non-empty-string,\ServiceBus\Endpoint\Endpoint>
     *
     * @var \ServiceBus\Endpoint\Endpoint[]
     */
    private $globalEndpoints = [];

    public function __construct(Endpoint $defaultEndpoint)
    {
        $this->addGlobalDestination($defaultEndpoint);
    }

    /**
     * Adding global delivery route.
     */
    public function addGlobalDestination(Endpoint $endpoint): void
    {
        $this->globalEndpoints[$endpoint->name()] = $endpoint;
    }

    /**
     * Add custom endpoint for multiple messages.
     *
     * @psalm-param list<class-string> $messages
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
     */
    public function registerRoute(string $messageClass, Endpoint $endpoint): void
    {
        $this->routes[$messageClass][] = $endpoint;
    }

    /**
     * Receiving a message sending route
     * If no specific route is registered, the default endpoint route will be returned.
     *
     * @psalm-return list<\ServiceBus\Endpoint\Endpoint>
     */
    public function route(string $messageClass): array
    {
        if (empty($this->routes[$messageClass]) === false)
        {
            return $this->routes[$messageClass];
        }

        return \array_values($this->globalEndpoints);
    }

    /**
     * Receive endpoint by index.
     *
     * @psalm-param non-empty-string $withIndex
     *
     * @throws \RuntimeException
     */
    public function endpoint(string $withIndex): Endpoint
    {
        if (isset($this->globalEndpoints[$withIndex]))
        {
            return $this->globalEndpoints[$withIndex];
        }

        throw new \RuntimeException(\sprintf('Unable to find endpoint with index `%s`', $withIndex));
    }
}
