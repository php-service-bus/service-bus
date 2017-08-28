<?php

/**
 * CQRS/Event Sourcing Non-blocking concurrency framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Infrastructure\MessageRouter;

use Desperado\ConcurrencyFramework\Domain\MessageRouter\MessageRouterInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;

/**
 * Message router
 */
class MessageRouter implements MessageRouterInterface
{
    /**
     * Message routes
     *
     * [
     *     'SomeMessageNamespace' => [
     *          0 => 'destinationExchange',
     *          1 => 'destinationExchange',
     *          ...
     *      ]
     * ]
     *
     * @var array
     */
    private $routes = [];

    /**
     * Application command destination exchange
     *
     * @var string
     */
    private $appCommandDestination;

    /**
     * Application event destination exchange
     *
     * @var string
     */
    private $appEventDestination;

    /**
     * @param string $appCommandDestination
     * @param string $ppEventDestination
     * @param array  $routes
     */
    public function __construct(
        string $appCommandDestination,
        string $ppEventDestination,
        array $routes = []
    )
    {
        $this->appCommandDestination = $appCommandDestination;
        $this->appEventDestination = $ppEventDestination;

        if(0 !== \count($routes))
        {
            $this->addRoutes($routes);
        }
    }

    /**
     * @inheritdoc
     */
    public function getApplicationExchanges(): array
    {
        return \array_unique([$this->appCommandDestination, $this->appEventDestination]);
    }

    /**
     * @inheritdoc
     */
    public function addRoutes(array $routes): self
    {
        foreach($routes as $destinationExchange => $messages)
        {
            if(false === \is_array($messages))
            {
                continue;
            }

            foreach($messages as $messageNamespace)
            {
                if(false === \class_exists($messageNamespace))
                {
                    continue;
                }

                $this->routes[$messageNamespace][] = $destinationExchange;
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function routeMessage(MessageInterface $message): array
    {
        $messageClass = \get_class($message);

        if(true === \array_key_exists($messageClass, $this->routes))
        {
            return $this->routes[$messageClass];
        }

        return $message instanceof EventInterface
            ? [$this->appEventDestination]
            : [$this->appCommandDestination];
    }
}
