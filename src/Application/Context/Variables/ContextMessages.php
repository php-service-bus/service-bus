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

namespace Desperado\Framework\Application\Context\Variables;

use Desperado\Framework\Domain\MessageRouter\MessageRouterInterface;
use Desperado\Framework\Domain\Messages\CommandInterface;
use Desperado\Framework\Domain\Messages\MessageInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryContextInterface;
use Desperado\Framework\Infrastructure\CQRS\Context\DeliveryOptions;

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
     * @param DeliveryContextInterface $originContext
     * @param MessageRouterInterface   $messagesRouter
     */
    public function __construct(
        DeliveryContextInterface $originContext,
        MessageRouterInterface $messagesRouter
    )
    {
        $this->originContext = $originContext;
        $this->messagesRouter = $messagesRouter;
    }

    /**
     * @param MessageInterface $message
     * @param DeliveryOptions  $deliveryOptions
     *
     * @return void
     */
    public function deliveryMessage(MessageInterface $message, DeliveryOptions $deliveryOptions): void
    {
        $messageDeliveryMethod = $message instanceof CommandInterface ? 'send' : 'publish';
        $routes = $this->messagesRouter->routeMessage($message);

        if(0 !== \count($routes))
        {
            foreach($routes as $route)
            {
                $deliveryContext = $deliveryOptions->changeDestination($route);

                $this->originContext->$messageDeliveryMethod($message, $deliveryContext);
            }

            return;
        }

        $this->originContext->$messageDeliveryMethod($message, $deliveryOptions);
    }
}
