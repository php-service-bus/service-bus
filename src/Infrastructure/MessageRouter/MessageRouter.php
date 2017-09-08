<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework\Infrastructure\MessageRouter;

use Desperado\Framework\Domain\MessageRouter\MessageRouterInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;
use Psr\Log\LoggerInterface;

/**
 * Message router
 */
class MessageRouter implements MessageRouterInterface
{
    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

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
     * @param array           $messageRoutes
     * @param LoggerInterface $logger
     */
    public function __construct(array $messageRoutes, LoggerInterface $logger)
    {
        $this->logger = $logger;

        if(0 !== \count($messageRoutes))
        {
            $this->addRoutes($messageRoutes);
        }
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
                $this->logger->debug(
                    \sprintf(
                        'The list of routes for destination "%s" is empty. The configuration is not needed',
                        $destinationExchange
                    )
                );

                continue;
            }

            foreach($messages as $messageNamespace)
            {
                if(false === \class_exists($messageNamespace))
                {
                    $this->logger->error(
                        \sprintf(
                            'Message class "%s" for route "%s" not found',
                            $messageNamespace, $destinationExchange
                        )
                    );

                    continue;
                }

                $this->routes[$messageNamespace][] = $destinationExchange;

                $this->logger->debug(
                    \sprintf(
                        'The route for the "%s" message and the destination "%s" was successfully added',
                        $messageNamespace, $destinationExchange
                    )
                );
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

        return [];
    }
}
