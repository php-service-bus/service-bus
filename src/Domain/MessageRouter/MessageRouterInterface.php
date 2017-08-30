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

namespace Desperado\ConcurrencyFramework\Domain\MessageRouter;

use Desperado\ConcurrencyFramework\Domain\Messages\MessageInterface;

/**
 * Messages (command/events) response router
 */
interface MessageRouterInterface
{
    /**
     * Add routes collection
     *
     * @param array $routes
     *
     * @return $this
     */
    public function addRoutes(array $routes);

    /**
     * Get destination for specified message
     *
     * @param MessageInterface $message
     *
     * @return array
     */
    public function routeMessage(MessageInterface $message): array;
}
