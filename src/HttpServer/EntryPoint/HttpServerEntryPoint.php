<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\HttpServer\EntryPoint;

use Desperado\Domain\Message\AbstractMessage;
use Desperado\ServiceBus\Application\Context\ExecutionContextInterface;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Domain\ThrowableFormatter;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContextInterface;
use Desperado\ServiceBus\Transport\Message\Message;
use Desperado\Infrastructure\Bridge\Publisher\PublisherInterface;
use Desperado\Infrastructure\Bridge\Router\Exceptions\HttpException;
use Desperado\Infrastructure\Bridge\Router\RouterInterface;
use Desperado\ServiceBus\Application\EntryPoint\EntryPointContext;
use Desperado\ServiceBus\Application\EntryPoint\EntryPointInterface;
use Desperado\ServiceBus\Application\Kernel\AbstractKernel;
use Desperado\ServiceBus\HttpServer\Context\HttpIncomingContext;
use Desperado\ServiceBus\HttpServer\Context\OutboundHttpContextInterface;
use Desperado\ServiceBus\HttpServer\HttpServerBackendInterface;
use Desperado\ServiceBus\Services\Handlers\MessageHandlerData;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContext;
use function GuzzleHttp\Promise\all;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use React\Promise\PromiseInterface;
use RingCentral\Psr7\Response;

/**
 * Http entry point
 */
class HttpServerEntryPoint implements EntryPointInterface
{
    /**
     * Entry point name
     *
     * @var string
     */
    private $name;

    /**
     * Application kernel
     *
     * @var AbstractKernel
     */
    private $kernel;

    /**
     * Application-level execution context
     *
     * @var ExecutionContextInterface
     */
    private $executionContext;

    /**
     * Message transport
     *
     * @var PublisherInterface
     */
    private $publisher;

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
     * @param string                     $name
     * @param AbstractKernel             $kernel
     * @param ExecutionContextInterface  $executionContext
     * @param PublisherInterface         $publisher
     * @param HttpServerBackendInterface $backend
     * @param RouterInterface            $router
     * @param MessageSerializerInterface $messageSerializer
     * @param LoggerInterface            $logger
     */
    public function __construct(
        string $name,
        AbstractKernel $kernel,
        ExecutionContextInterface $executionContext,
        PublisherInterface $publisher,
        HttpServerBackendInterface $backend,
        RouterInterface $router,
        MessageSerializerInterface $messageSerializer,
        LoggerInterface $logger
    )
    {
        $this->name              = $name;
        $this->kernel            = $kernel;
        $this->executionContext  = $executionContext;
        $this->publisher         = $publisher;
        $this->backend           = $backend;
        $this->router            = $router;
        $this->messageSerializer = $messageSerializer;
        $this->logger            = $logger;

        \pcntl_async_signals(true);

        \pcntl_signal(\SIGINT, [$this, 'stop']);
        \pcntl_signal(\SIGTERM, [$this, 'stop']);
    }


    /**
     * @inheritdoc
     *
     * @throws \Exception
     */
    public function run(): void
    {
        $this->backend->listen(
            function(callable $resolve, ServerRequestInterface $request)
            {
                try
                {
                    $promise = $this->handleRequest(
                        $request,
                        $this->matchRequest($request)
                    );

                    $this->processPromises($promise, $resolve);
                }
                catch(\Throwable $throwable)
                {
                    $resolve($this->createFailedResponse($throwable));
                }
            },
            function($resolve, \Throwable $throwable)
            {
                $resolve($this->createFailedResponse($throwable));
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function stop(): void
    {
        $this->backend->disconnect();
    }

    /**
     * Process result promises
     *
     * @param PromiseInterface $promise
     * @param callable         $resolve
     *
     * @return PromiseInterface
     *
     * @throws \Desperado\Domain\MessageSerializer\Exceptions\MessageSerializationFailException
     */
    private function processPromises(PromiseInterface $promise, callable $resolve): PromiseInterface
    {
        return $promise
            ->then(
                function(array $contexts = null) use ($resolve)
                {
                    $promises = [];

                    if(true === \is_array($contexts) && 0 !== \count($contexts))
                    {
                        foreach($contexts as $context)
                        {
                            /** @var Message $message */

                            foreach(\iterator_to_array($context->getToPublishMessages()) as $message)
                            {
                                $promises[] = $this->publisher->publish(
                                    $message->getExchange(),
                                    $message->getRoutingKey(),
                                    $message->getBody(),
                                    $message->getHeaders()->all()
                                );
                            }

                            if(
                                $context instanceof OutboundHttpContextInterface &&
                                true === $context->responseBind() &&
                                !$responseData = $context->getResponseData()
                            )
                            {
                                $resolve(
                                    $this->createResponse(
                                        $responseData->getCode(),
                                        $responseData->getHeaders(),
                                        $responseData->getBody()
                                    )
                                );
                            }
                        }
                    }

                    return all($promises);
                }
            )
            ->then(
                null,
                function(\Throwable $throwable) use ($resolve)
                {
                    $resolve($this->createFailedResponse($throwable));
                }
            );
    }

    /**
     * Handle request
     *
     * @param ServerRequestInterface $request
     * @param AbstractMessage        $message
     *
     * @return PromiseInterface
     * @throws \InvalidArgumentException
     */
    private function handleRequest(ServerRequestInterface $request, AbstractMessage $message): PromiseInterface
    {
        $httpIncomingContext = HttpIncomingContext::fromRequest(
            $request, $message, (string) $request->getBody(), $this->name
        );

        $entryPointContext = EntryPointContext::create(
            $httpIncomingContext->getUnpackedMessage(),
            $httpIncomingContext->getReceivedMessage()->getHeaders()
        );
        $outboundContext   = OutboundMessageContext::fromHttpRequest(
            $request,
            $httpIncomingContext,
            $this->messageSerializer
        );

        $executionContext = $this->executionContext->applyOutboundMessageContext($outboundContext);

        return $this->kernel->handle($entryPointContext, $executionContext);
    }

    /**
     * Create response by throwable
     *
     * @param \Throwable $throwable
     *
     * @return ResponseInterface
     */
    private function createFailedResponse(\Throwable $throwable): ResponseInterface
    {
        $this->logger->error(ThrowableFormatter::toString($throwable));

        if($throwable instanceof HttpException)
        {
            return $this->createResponse($throwable->getHttpCode(), [], $throwable->getResponseMessage());
        }

        return $this->createResponse(500);
    }

    /**
     * Create response object
     *
     * @param int         $status
     * @param array       $headers
     * @param null|string $body
     *
     * @return ResponseInterface
     */
    private function createResponse(int $status = 200, array $headers = [], ?string $body = null): ResponseInterface
    {
        return new Response($status, $headers, $body);
    }

    /**
     * Search message by request parameters
     *
     * @param ServerRequestInterface $request
     *
     * @return AbstractMessage
     *
     * @throws \Desperado\Infrastructure\Bridge\Router\Exceptions\HttpException
     */
    private function matchRequest(ServerRequestInterface $request): AbstractMessage
    {
        $closure = $this->router->match($request->getUri()->getPath(), $request->getMethod());

        /** @var MessageHandlerData $handler */
        $handler = $closure();

        return \call_user_func(
            \sprintf('%s::fromRequest', $handler->getMessageClassNamespace()),
            $request
        );
    }
}
