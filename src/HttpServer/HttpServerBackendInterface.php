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
     * @param callable $requestHandler   function(ServerRequestInterface $request)
     *                                   {
     *                                   return $response; // RingCentral\Psr7\Response
     *                                   }
     *
     * @param callable $throwableHandler function(\Throwable $throwable)
     *                                   {
     *                                   return $response; // RingCentral\Psr7\Response
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
