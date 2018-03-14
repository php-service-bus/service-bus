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

use Evenement\EventEmitter;
use EventLoop\EventLoop;
use GuzzleHttp\Psr7\BufferStream;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\Http\StreamingServer;
use React\Promise\Promise;
use React\Socket\Server as SocketServer;
use React\Socket\SecureServer;
use React\Socket\ServerInterface;

/**
 * ReactHttp web server
 */
final class ReactHttpServerBackend implements HttpServerBackendInterface
{
    /**
     * Configuration
     *
     * @var HttpServerConfiguration
     */
    private $config;

    /**
     * Logger instance
     *
     * @var LoggerInterface
     */
    private $logger;
    /**
     * React socket server
     *
     * @var ServerInterface
     */
    private $server;

    /**
     * @param HttpServerConfiguration $config
     * @param LoggerInterface         $logger
     */
    public function __construct(HttpServerConfiguration $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     *
     * @throws \InvalidArgumentException
     * @throws \BadMethodCallException
     */
    public function listen(callable $requestHandler, callable $throwableHandler): void
    {
        $this
            ->initSocketServer()
            ->initHttpServer($requestHandler, $throwableHandler)
            ->listen($this->server);

        $this->logger->info(
            \sprintf('Http server started on "%s"', $this->server->getAddress())
        );

        EventLoop::getLoop()->run();
    }

    /**
     * @inheritdoc
     */
    public function disconnect(): void
    {
        $this->server->close();

        EventLoop::getLoop()->stop();
    }

    /**
     * Create http server
     *
     * @see self::listen() description
     *
     * @param callable $requestHandler
     * @param callable $throwableHandler
     *
     * @return StreamingServer
     *
     * @throws \InvalidArgumentException
     */
    private function initHttpServer(callable $requestHandler, callable $throwableHandler): StreamingServer
    {
        return new StreamingServer(
            function(ServerRequestInterface $request) use ($requestHandler, $throwableHandler)
            {
                return new Promise(
                    function($resolve) use ($request, $requestHandler, $throwableHandler)
                    {
                        /** @var EventEmitter $eventEmitter */
                        $eventEmitter = $request->getBody();

                        $contentLength = 0;
                        $contentBody   = '';

                        $this->readStream($eventEmitter, $contentLength, $contentBody);
                        $this->onReadingStreamError($eventEmitter, $throwableHandler, $resolve);

                        $eventEmitter->on(
                            'end',
                            function() use (
                                $resolve, $request, &$contentLength, &$contentBody, $requestHandler, $throwableHandler
                            )
                            {

                                $this->logRequest($request, $contentBody);

                                try
                                {
                                    $stream = new BufferStream($contentLength);
                                    $stream->write($contentBody);

                                    $requestHandler(
                                        $resolve,
                                        $request->withBody($stream)

                                    );
                                }
                                catch(\Throwable $throwable)
                                {
                                    $throwableHandler($resolve, $throwable);
                                }
                            }
                        );
                    }
                );
            }
        );
    }

    /**
     * Log request
     *
     * @param ServerRequestInterface $request
     * @param string                 $contentBody
     *
     * @return void
     */
    private function logRequest(ServerRequestInterface $request, string $contentBody): void
    {
        $arrayToString = function(array $data)
        {
            return \urldecode(\http_build_query($data));
        };

        $this->logger->debug(
            \sprintf(
                '[%s] %s (parameters: %s). Headers: %s',
                $request->getMethod(),
                $request->getUri()->getPath(),
                'GET' === $request->getMethod()
                    ? $arrayToString($request->getQueryParams())
                    : $contentBody,
                $arrayToString($request->getHeaders())
            )
        );
    }

    /**
     * Read request body
     *
     * @param EventEmitter $eventEmitter
     * @param int          $contentLength
     * @param string       $contentBody
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function readStream(EventEmitter $eventEmitter, int &$contentLength, string &$contentBody): void
    {
        $eventEmitter->on(
            'data',
            function($data) use (&$contentLength, &$contentBody)
            {
                $contentBody   .= $data;
                $contentLength += \strlen($data);
            }
        );
    }

    /**
     * An error occured while reading stream
     *
     * @param EventEmitter $eventEmitter
     * @param callable     $throwableHandler
     * @param callable     $resolve
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    private function onReadingStreamError(EventEmitter $eventEmitter, callable $throwableHandler, callable $resolve): void
    {
        $eventEmitter->on(
            'error',
            function(\Throwable $throwable) use ($resolve, $throwableHandler)
            {
                $resolve(
                    $throwableHandler($throwable)
                );
            }
        );
    }

    /**
     * Init socket server
     *
     * @return $this
     *
     * @throws \BadMethodCallException
     */
    private function initSocketServer(): self
    {
        $this->server = new SocketServer(
            \sprintf('%s:%s', $this->config->getHots(), $this->config->getPort()),
            EventLoop::getLoop()
        );

        if(true === $this->config->isSecured())
        {
            $this->server = new SecureServer(
                $this->server,
                EventLoop::getLoop(),
                ['local_cert' => $this->config->getCertificateFilePath()]
            );
        }

        return $this;
    }
}
