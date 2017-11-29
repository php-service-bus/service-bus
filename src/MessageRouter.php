<?php

/**
 * CQRS/Event Sourcing framework
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\Framework;

use Desperado\Domain\EntryPoint\MessageRouterInterface;
use Desperado\Domain\Message\AbstractMessage;
use Desperado\Framework\Exceptions\EntryPointException;

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
     * @param array $routes
     */
    public function __construct(array $routes = [])
    {
        $this->addRoutes($routes);
    }

    /**
     * @inheritdoc
     */
    public function addRoutes(array $routes): void
    {
        foreach($routes as $destinationExchange => $messages)
        {
            $messages = true === \is_array($messages)
                ? $messages
                : [$messages];

            foreach($messages as $messageNamespace)
            {
                if(true === \class_exists($messageNamespace))
                {
                    $this->routes[$messageNamespace][] = $destinationExchange;
                }
                else
                {
                    throw new EntryPointException(
                        \sprintf('Message class "%s" not exists', $messageNamespace)
                    );
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function routeMessage(AbstractMessage $message): array
    {
        $messageClass = \get_class($message);

        if(true === \array_key_exists($messageClass, $this->routes))
        {
            return $this->routes[$messageClass];
        }

        return [];
    }
}
