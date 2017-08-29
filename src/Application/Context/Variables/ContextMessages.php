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

namespace Desperado\ConcurrencyFramework\Application\Context\Variables;

use Desperado\ConcurrencyFramework\Domain\MessageRouter\MessageRouterInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\CommandInterface;
use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\ConcurrencyFramework\Infrastructure\CQRS\Context\DeliveryOptions;

/**
 * Context messages DTO
 */
class ContextMessages
{
    /**
     * Parent execution context
     *
     * @var DeliveryContextInterface
     */
    private $originContext;

    /**
     * Messages router
     *
     * @var MessageRouterInterface
     */
    private $messagesRouter;

    /**
     * Logger context
     *
     * @var ContextLogger
     */
    private $contextLogger;

    /**
     * @param DeliveryContextInterface $originContext
     * @param MessageRouterInterface   $messagesRouter
     * @param ContextLogger            $contextLogger
     */
    public function __construct(
        DeliveryContextInterface $originContext,
        MessageRouterInterface $messagesRouter,
        ContextLogger $contextLogger
    )
    {
        $this->originContext = $originContext;
        $this->messagesRouter = $messagesRouter;
        $this->contextLogger = $contextLogger;
    }

    /**
     * Get default destination for message
     *
     * @param MessageInterface $message
     *
     * @return string
     */
    public function getDefaultDestinationForMessage(MessageInterface $message): string
    {
        $destinations = $this->messagesRouter->getApplicationExchanges();
        $destinationsCount = \count($destinations);

        if(1 === $destinationsCount)
        {
            return $destinations[0];
        }
        else if(2 === $destinationsCount)
        {
            return $message instanceof CommandInterface ? $destinations[0] : $destinations[1];
        }

        return '';
    }

    /**
     * @param MessageInterface $message
     * @param DeliveryOptions  $deliveryOptions
     *
     * @return void
     */
    public function deliveryMessage(MessageInterface $message, DeliveryOptions $deliveryOptions): void
    {
        $messageClass = \get_class($message);
        $messageDeliveryMethod = $message instanceof CommandInterface ? 'send' : 'publish';
        $routes = $this->messagesRouter->routeMessage($message);

        if(0 !== \count($routes))
        {
            foreach($routes as $route)
            {
                $deliveryContext = $deliveryOptions->changeDestination($route);

                $destination = (string) $deliveryContext->getDestination();

                if('' !== $destination)
                {
                    $this->originContext->$messageDeliveryMethod($message, $deliveryContext);

                    $this->contextLogger
                        ->getLogger('messages')
                        ->debug(
                            'Message "{messageType}" was sent to the "{destination}" exchange',
                            ['messageType' => $messageClass, 'destination' => $destination]
                        );
                }
                else
                {
                    $this->contextLogger
                        ->getLogger('messages')
                        ->error(
                            'Not found destination queue for message "{messageType}"',
                            ['messageType' => $messageClass]
                        );
                }
            }

            return;
        }

        $this->contextLogger
            ->getLogger('messages')
            ->error(
                'Not found routes for message "{messageType}"',
                ['messageType' => $messageClass]
            );
    }
}
