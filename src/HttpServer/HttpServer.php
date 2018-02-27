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

use Desperado\Infrastructure\Bridge\Router\FastRouterBridge;
use Desperado\Infrastructure\Bridge\Router\RouterInterface;

/**
 * Http server
 */
class HttpServer
{
    /**
     * Http server backend
     *
     * @var HttpServerBackendInterface
     */
    private $backend;

    /**
     * Router
     *
     * @var RouterInterface
     */
    private $router;

    /**
     * @param HttpServerBackendInterface $backend
     * @param RouterInterface            $router
     */
    public function __construct(HttpServerBackendInterface $backend, RouterInterface $router)
    {
        $this->backend = $backend;
        $this->router = $router;

        \pcntl_async_signals(true);

        \pcntl_signal(\SIGINT, [$this, 'stop']);
        \pcntl_signal(\SIGTERM, [$this, 'stop']);
    }

    /**
     * Start http server
     *
     * @return void
     */
    public function start(): void
    {

    }

    /**
     * Stop http server
     *
     * @return void
     */
    public function stop(): void
    {
        $this->backend->disconnect();
    }
}
