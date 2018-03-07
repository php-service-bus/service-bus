<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\HttpServer;

/**
 * Http server backend
 */
interface HttpServerBackendInterface
{
    /**
     * @param callable $requestHandler   function(callable $resolve, ServerRequestInterface $request)
     *                                   {
     *                                   return $response; // Psr\Http\Message\ResponseInterface
     *                                   }
     *
     * @param callable $throwableHandler function(callable $resolve, \Throwable $throwable)
     *                                   {
     *                                   return $response; // Psr\Http\Message\ResponseInterface
     *                                   }
     *
     *
     *
     * @return void
     */
    public function listen(callable $requestHandler, callable $throwableHandler): void;

    /**
     * Shuts down this listening socket
     *
     * @return void
     */
    public function disconnect(): void;

}
