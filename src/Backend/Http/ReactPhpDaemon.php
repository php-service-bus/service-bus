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

namespace Desperado\Framework\Backend\Http;

use Desperado\Domain\ParameterBag;
use Desperado\Framework\Application\ApplicationLogger;
use Desperado\Infrastructure\Bridge\Publisher\PublisherInterface;
use Desperado\Infrastructure\Bridge\Router\Exceptions\HttpException;
use EventLoop\EventLoop;
use function GuzzleHttp\Psr7\parse_query;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Http\Server as HttpServer;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use React\Socket\Server as SocketServer;
use Desperado\Domain\DaemonInterface;
use Desperado\Domain\EntryPointInterface;
use Desperado\Infrastructure\Bridge\Router\RouterInterface;
use React\Socket\ServerInterface;
use React\Stream\CompositeStream;
use React\Stream\ThroughStream;

/**
 * ReactPHP daemon
 */
class ReactPhpDaemon implements DaemonInterface
{
    protected const LOG_CHANNEL_NAME = 'reactPHP';

    /**
     * Is https server
     *
     * @var bool
     */
    private $isSecured;

    /**
     * Pem file (ssl certificate) file path
     *
     * @var string|null
     */
    private $certificateFilePath;

    /**
     * Server host DSN
     *
     * @var string
     */
    private $reactDSN;

    /**
     * Http router
     *
     * @var RouterInterface
     */
    private $router;

    /**
     * React socket server
     *
     * @var ServerInterface
     */
    private $socketServer;

    /**
     * Default routing key
     *
     * @var string
     */
    private $publisherRoutingKey;

    /**
     * Publisher
     *
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * [
     *     'dsn'             => 'tls://0.0.0.0:1337',
     *     'certificatePath' => null
     * ]
     *
     * @param array              $daemonOptions
     * @param RouterInterface    $router
     * @param PublisherInterface $publisher
     * @param string             $publisherRoutingKey
     *
     * @throws \LogicException
     */
    public function __construct(
        array $daemonOptions,
        RouterInterface $router,
        PublisherInterface $publisher,
        string $publisherRoutingKey
    )
    {
        $parameters = new ParameterBag($daemonOptions);
        $dsnParameters = new ParameterBag(\parse_url($daemonOptions['dsn'] ?? 'tcp://0.0.0.0:1337'));

        $this->isSecured = 'tls' === $dsnParameters->getAsString('scheme', 'tcp');
        $this->certificateFilePath = $parameters->getAsString('certificatePath');
        $this->reactDSN = $parameters->getAsString('dsn');
        $this->router = $router;
        $this->publisher = $publisher;
        $this->publisherRoutingKey = $publisherRoutingKey;

        if(
            true === $this->isSecured &&
            false === (
                true === \file_exists($this->certificateFilePath) &&
                true === \is_readable($this->certificateFilePath)
            )
        )
        {
            throw new \LogicException('Invalid ssl certificate file path');
        }

        $this->initSignals();
    }

    /**
     * @inheritdoc
     */
    public function run(EntryPointInterface $entryPoint, array $clients = []): void
    {
        $server = new HttpServer(
            function(ServerRequestInterface $request) use ($entryPoint)
            {
                $requestPromise = new Promise(
                    function($resolve, $reject) use ($request, $entryPoint)
                    {
                        $requestHandler = function(ServerRequestInterface $request, ?string $requestBody = null) use (
                            $entryPoint, $resolve, $reject
                        )
                        {
                            try
                            {
                                $handlerData = $this->router->match($request->getRequestTarget(), $request->getMethod());
                                $messageNamespace = (string) $handlerData();

                                if('' !== $messageNamespace && true === \class_exists($messageNamespace))
                                {
                                    /** @var \Desperado\Domain\Messages\AbstractQueryMessage $message */
                                    $message = new $messageNamespace(
                                        (string ) $request->getMethod(),
                                        (string ) $request->getRequestTarget(),
                                        (string ) $requestBody,
                                        $request->getQueryParams(),
                                        $request->getServerParams(),
                                        $request->getCookieParams(),
                                        $request->getHeaders(),
                                        $request->getUploadedFiles()
                                    );

                                    $serializer = $entryPoint->getMessageSerializer();

                                    $context = new ReactPhpContext(
                                        $serializer,
                                        $this->publisher,
                                        $entryPoint->getEntryPointName(),
                                        $this->publisherRoutingKey
                                    );

                                    /** @var PromiseInterface $handlerMessagePromise */
                                    $handlerMessagePromise = $entryPoint->handleMessage($message, $context);

                                    $handlerMessagePromise->then(
                                        function() use ($resolve, $reject, $context)
                                        {
                                            try
                                            {
                                                return $resolve($context->getResponse());
                                            }
                                            catch(\Throwable $throwable)
                                            {
                                                return $reject($throwable);
                                            }
                                        },
                                        function(\Throwable $throwable) use ($reject)
                                        {
                                            return $reject($throwable);
                                        }
                                    );
                                }
                            }
                            catch(\Throwable $throwable)
                            {
                                return $reject($throwable);
                            }
                        };

                        if(true === \in_array($request->getMethod(), ['GET', 'HEAD', 'DELETE']))
                        {
                            return $requestHandler($request);
                        }
                        else
                        {
                            $request->getBody()->on(
                                'data',
                                function(string $requestBody) use ($request, $requestHandler)
                                {
                                    return $requestHandler($request, $requestBody);
                                }
                            );
                        }
                    }
                );

                return $requestPromise
                    ->then(
                        function(Response $response)
                        {
                            ApplicationLogger::debug(
                                self::LOG_CHANNEL_NAME,
                                \sprintf(
                                    'Render response with http code "%s" and body: "%s"',
                                    $response->getStatusCode(), (string) $response->getBody()
                                )
                            );

                            return $response;
                        },
                        function(\Throwable $throwable)
                        {
                            ApplicationLogger::throwable(self::LOG_CHANNEL_NAME, $throwable);

                            return new Response(500, 'Application error');
                        }
                    );
            }
        );

        $this->socketServer = new SocketServer(
            $this->reactDSN,
            EventLoop::getLoop(),
            ['local_cert' => $this->certificateFilePath]
        );

        $server->listen($this->socketServer);

        ApplicationLogger::info(
            self::LOG_CHANNEL_NAME,
            \sprintf(
                'ReactPHP daemon started on %s',
                \str_replace(['tcp:', 'tls:'], ['http:', 'https:'], $this->socketServer->getAddress())
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function stop(): void
    {
        $this->socketServer->close();

        EventLoop::getLoop()->stop();

        ApplicationLogger::info(self::LOG_CHANNEL_NAME, 'ReactPHP daemon stopped');
    }

    /**
     * Init unix signals
     *
     * @return void
     */
    private function initSignals(): void
    {
        \pcntl_signal(\SIGINT, [$this, 'stop']);
        \pcntl_signal(\SIGTERM, [$this, 'stop']);

        \pcntl_async_signals(true);
    }
}
