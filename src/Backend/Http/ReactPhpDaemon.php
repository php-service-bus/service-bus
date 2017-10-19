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

use Desperado\Domain\Messages\QueryMessage;
use Desperado\Domain\ParameterBag;
use Desperado\Framework\Application\ApplicationLogger;
use Desperado\Infrastructure\Bridge\Publisher\PublisherInterface;
use EventLoop\EventLoop;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Request;
use React\Http\Response;
use React\Http\Server as HttpServer;
use React\Promise\PromiseInterface;
use React\Socket\Server as SimpleSocketServer;
use React\Socket\SecureServer as SecuredSocketServer;
use Desperado\Domain\DaemonInterface;
use Desperado\Domain\EntryPointInterface;
use Desperado\Infrastructure\Bridge\Router\RouterInterface;
use React\Socket\ServerInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

/**
 * ReactPHP daemon
 */
class ReactPhpDaemon implements DaemonInterface
{
    protected const LOG_CHANNEL_NAME = 'reactPhp';
    protected const DEFAULT_HOST = '0.0.0.0:0';
    protected const DEFAULT_PORT = 1337;

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
     * Listen host
     *
     * @var string
     */
    private $listenHost;

    /**
     * Listen port
     *
     * @var int
     */
    private $listenPort;

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
     *     'isSecured'       => false,
     *     'certificatePath' => null,
     *     'host'            => '127.0.0.1',
     *     'port'            => 1337
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

        $this->isSecured = (bool) $parameters->getAsInt('isSecured');
        $this->certificateFilePath = $parameters->getAsString('certificatePath');
        $this->listenHost = $parameters->getAsString('host', self::DEFAULT_HOST);
        $this->listenPort = $parameters->getAsInt('port', self::DEFAULT_PORT);
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
        $this->socketServer = new SimpleSocketServer(EventLoop::getLoop());

        if(true === $this->isSecured)
        {
            $this->socketServer = new SecuredSocketServer(
                $this->socketServer,
                EventLoop::getLoop(),
                ['local_cert' => $this->certificateFilePath]
            );
        }

        $httpServer = new HttpServer($this->socketServer);

        $httpServer->on(
            'request',
            function(Request $request, Response $response) use ($entryPoint)
            {
                $request->on(
                    'data',
                    function($data) use ($request, $response, $entryPoint)
                    {
                        $this->handleRequest($entryPoint, $request, $response, (string) $data);
                    }
                );

                $this->handleRequest($entryPoint, $request, $response);
            }
        );

        $httpServer->on(
            'error',
            function(\Throwable $throwable, Response $response)
            {
                ApplicationLogger::throwable(self::LOG_CHANNEL_NAME, $throwable);

                $response->writeHead(500);
                $response->end('Internal error');
            }
        );

        $this->socketServer->listen($this->listenPort, $this->listenHost);

        ApplicationLogger::info(
            self::LOG_CHANNEL_NAME,
            \sprintf('"%s" created', \get_class(EventLoop::getLoop()))
        );

        ApplicationLogger::info(
            self::LOG_CHANNEL_NAME,
            \sprintf('ReactPHP daemon started. Listen: %s:%s', $this->listenHost, $this->listenPort)
        );

        EventLoop::getLoop()->run();
    }

    /**
     * @inheritdoc
     */
    public function stop(): void
    {
        $this->socketServer->shutdown();

        EventLoop::getLoop()->stop();

        ApplicationLogger::info(self::LOG_CHANNEL_NAME, 'ReactPHP daemon stopped');
    }

    /**
     * Execute request
     *
     * @param EntryPointInterface $entryPoint
     * @param Request             $request
     * @param Response            $response
     * @param string|null         $bodyContent
     *
     * @return void
     */
    private function handleRequest(
        EntryPointInterface $entryPoint,
        Request $request,
        Response $response,
        ?string $bodyContent = null
    ): void
    {
        $serverRequest = $this->convertRequestInstance($request, (string) $bodyContent);
        $serializer = $entryPoint->getMessageSerializer();

        $context = new ReactPhpContext(
            $serverRequest,
            $response,
            $serializer,
            $this->publisher,
            $entryPoint->getEntryPointName(),
            $this->publisherRoutingKey
        );

        $message = QueryMessage::fromRequest($serverRequest);

        $result = $entryPoint->handleMessage($message, $context);

        if($result instanceof PromiseInterface)
        {
            $result->then(
                function() use ($response)
                {
                    $response->end();
                }
            );
        }
        else if(true === \is_scalar($result))
        {
            $response->end((string) $result);
        }
    }

    /**
     * Create psr server request instance
     *
     * @param Request $request
     * @param string  $bodyContent
     *
     * @return ServerRequestInterface
     */
    protected function convertRequestInstance(Request $request, string $bodyContent): ServerRequestInterface
    {
        $bodyStream = new Stream('php://temp', 'wb+');
        $bodyStream->write($bodyContent);
        $bodyStream->rewind();

        return new ServerRequest(
            ['REMOTE_ADDR' => $request->remoteAddress],
            [],
            $request->getPath(),
            $request->getMethod(),
            $bodyStream,
            $request->getHeaders(),
            [],
            $request->getQuery()
        );
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
