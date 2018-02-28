<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\EntryPoint;

use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\Domain\MessageSerializer\MessageSerializerInterface;
use Desperado\Domain\Transport\Context\OutboundMessageContextInterface;
use Desperado\Domain\Transport\Message\Message;
use Desperado\Infrastructure\Bridge\Publisher\PublisherInterface;
use Desperado\ServiceBus\Application\Kernel\AbstractKernel;
use Desperado\ServiceBus\HttpServer\Context\HttpIncomingContext;
use Desperado\ServiceBus\HttpServer\HttpServer;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContext;
use Psr\Http\Message\RequestInterface;

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
     * Http server
     *
     * @var HttpServer
     */
    private $httpServer;

    /**
     * Message transport
     *
     * @var PublisherInterface
     */
    private $publisher;

    /**
     * @param string                    $name
     * @param AbstractKernel            $kernel
     * @param ExecutionContextInterface $executionContext
     * @param PublisherInterface        $publisher
     * @param HttpServer                $httpServer
     */
    public function __construct(
        string $name,
        AbstractKernel $kernel,
        ExecutionContextInterface $executionContext,
        PublisherInterface $publisher,
        HttpServer $httpServer
    )
    {
        $this->name = $name;
        $this->kernel = $kernel;
        $this->executionContext = $executionContext;
        $this->publisher = $publisher;
        $this->httpServer = $httpServer;
    }

    /**
     * @inheritdoc
     */
    public function run(array $clients = []): void
    {
        $this->httpServer->start(
            $this->name,
            function(RequestInterface $request, HttpIncomingContext $httpIncomingContext, MessageSerializerInterface $messageSerializer)
            {
                $entryPointContext = EntryPointContext::create(
                    $httpIncomingContext->getUnpackedMessage(),
                    $httpIncomingContext->getReceivedMessage()->getHeaders()
                );

                $outboundContext = OutboundMessageContext::fromHttpRequest(
                    $request,
                    $httpIncomingContext,
                    $messageSerializer
                );

                $executionContext = $this->executionContext->applyOutboundMessageContext($outboundContext);

                $promise = $this->kernel->handle($entryPointContext, $executionContext);

                return $promise->then(
                    function(array $contexts = null)
                    {
                        if(false === \is_array($contexts) || 0 === \count($contexts))
                        {
                            return;
                        }

                        $promises = [];

                        foreach($contexts as $context)
                        {
                            /** @var OutboundMessageContextInterface $context */

                            $promises = \array_merge(
                                \array_map(
                                    function(Message $message)
                                    {
                                        return $this->publisher->publish(
                                            $message->getExchange(),
                                            $message->getRoutingKey(),
                                            $message->getBody(),
                                            $message->getHeaders()->all()
                                        );
                                    },
                                    \iterator_to_array($context->getToPublishMessages())
                                ),
                                $promises
                            );
                        }
                    },
                    function(\Throwable $throwable)
                    {
                        die('failed');
                    }
                );
            }
        );
    }

    /**
     * @inheritdoc
     */
    public function stop(): void
    {
        $this->httpServer->stop();
        $this->transport->disconnect();
    }
}
