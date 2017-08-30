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
     * Entry point
     *
     * @var string
     */
    private $entryPointName;

    /**
     * @param string                   $entryPointName
     * @param DeliveryContextInterface $originContext
     * @param MessageRouterInterface   $messagesRouter
     * @param ContextLogger            $contextLogger
     */
    public function __construct(
        string $entryPointName,
        DeliveryContextInterface $originContext,
        MessageRouterInterface $messagesRouter,
        ContextLogger $contextLogger
    )
    {
        $this->entryPointName = $entryPointName;
        $this->originContext = $originContext;
        $this->messagesRouter = $messagesRouter;
        $this->contextLogger = $contextLogger;
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

        $deliveryContext = $deliveryOptions->changeDestination($this->entryPointName);
        $this->originContext->$messageDeliveryMethod($message, $deliveryContext);
    }
}
