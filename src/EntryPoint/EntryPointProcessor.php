<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 *
 * @author  Maksim Masiukevich <dev@async-php.com>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace ServiceBus\EntryPoint;

use Amp\Promise;
use ServiceBus\Endpoint\EndpointRouter;
use ServiceBus\Transport\Common\Package\IncomingPackage;

/**
 * Handling incoming package processor
 */
interface EntryPointProcessor
{
    /**
     * Adding an outbound router
     *
     * @param EndpointRouter $endpointRouter
     *
     * @return void
     */
    public function appendEndpointRouter(EndpointRouter $endpointRouter): void;

    /**
     * Handle package
     *
     * @param IncomingPackage $package
     *
     * @return Promise
     *
     * @throws \ServiceBus\EntryPoint\Exceptions\EndpointRouterNotConfigured
     */
    public function handle(IncomingPackage $package): Promise;
}
