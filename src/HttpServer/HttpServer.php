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

use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Domain\ThrowableFormatter;
use Desperado\Infrastructure\Bridge\Router\Exceptions\HttpException;
use Desperado\Infrastructure\Bridge\Router\RouterInterface;
use Desperado\ServiceBus\HttpServer\Context\HttpIncomingContext;
use Desperado\ServiceBus\Services\Handlers\MessageHandlerData;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use React\Http\Response;

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
     * Messages serialized
     *
     * @var MessageSerializerInterface
     */
    private $messageSerializer;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param HttpServerBackendInterface $backend
     * @param RouterInterface            $router
     * @param MessageSerializerInterface          $messageSerializer
     * @param LoggerInterface            $logger
     */
    public function __construct(
        HttpServerBackendInterface $backend,
        RouterInterface $router,
        MessageSerializerInterface $messageSerializer,
        LoggerInterface $logger
    )
    {
        $this->backend = $backend;
        $this->router = $router;
        $this->messageSerializer = $messageSerializer;
        $this->logger = $logger;

        \pcntl_async_signals(true);

        \pcntl_signal(\SIGINT, [$this, 'stop']);
        \pcntl_signal(\SIGTERM, [$this, 'stop']);
    }

    /**
     * Start http server
     *
     * @param string $entryPointName
     * @param callable function(HttpIncomingContext $httpIncomingContext) {}
     *
     * @return void
     */
    public function start(string $entryPointName, callable $callable): void
    {
        $this->backend->listen(
            function(RequestInterface $request) use ($entryPointName, $callable)
            {
                try
                {
                    $closure = $this->router->match($request->getUri()->getPath(), $request->getMethod());

                    /** @var MessageHandlerData $handler */
                    $handler = $closure();

                    $message = \call_user_func(
                        \sprintf('%s::fromRequest', $handler->getMessageClassNamespace()),
                        $request
                    );

                    $httpIncomingContext = HttpIncomingContext::fromRequest(
                        $request,
                        $message,
                        $this->messageSerializer->serialize($message),
                        $entryPointName
                    );

                    return $callable($request, $httpIncomingContext, $this->messageSerializer);
                }
                catch(HttpException $exception)
                {
                    return new Response(
                        $exception->getHttpCode(),
                        ['Content-Type' => 'text/plain'],
                        $exception->getResponseMessage()
                    );
                }
                catch(\Throwable $throwable)
                {
                    echo ThrowableFormatter::toString($throwable);
                    die();
                }
            },
            function(\Throwable $throwable)
            {
                echo ThrowableFormatter::toString($throwable);
                die();
            }
        );
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
