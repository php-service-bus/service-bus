<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\EntryPoint;

use function Amp\call;
use Amp\Promise;
use Desperado\ServiceBus\Application\KernelContext;
use Desperado\ServiceBus\Endpoint\EndpointRouter;
use Desperado\ServiceBus\Infrastructure\MessageSerialization\IncomingMessageDecoder;
use Desperado\ServiceBus\Infrastructure\Transport\Package\IncomingPackage;
use Desperado\ServiceBus\MessageRouter\Router;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 *
 */
final class DefaultEntryPointProcessor implements EntryPointProcessor
{
    /**
     * Decoding of incoming messages
     *
     * @var IncomingMessageDecoder
     */
    private $messageDecoder;

    /**
     * Outbound message routing
     *
     * @var EndpointRouter
     */
    private $endpointRouter;

    /**
     * @var Router
     */
    private $messagesRouter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IncomingMessageDecoder $messageDecoder
     * @param EndpointRouter         $endpointRouter
     * @param Router                 $messagesRouter
     * @param LoggerInterface        $logger
     */
    public function __construct(
        IncomingMessageDecoder $messageDecoder,
        EndpointRouter $endpointRouter,
        ?Router $messagesRouter = null,
        ?LoggerInterface $logger = null
    )
    {
        $this->messageDecoder = $messageDecoder;
        $this->endpointRouter = $endpointRouter;
        $this->messagesRouter = $messagesRouter ?? new Router();
        $this->logger         = $logger ?? new NullLogger();
    }

    /**
     * @inheritdoc
     */
    public function handle(IncomingPackage $package): Promise
    {
        $messageDecoder = $this->messageDecoder;
        $messagesRouter = $this->messagesRouter;
        $logger         = $this->logger;
        $endpointRouter = $this->endpointRouter;

        /** @psalm-suppress InvalidArgument Incorrect psalm unpack parameters (...$args) */
        return call(
            static function(IncomingPackage $package) use ($messageDecoder, $messagesRouter, $endpointRouter, $logger): \Generator
            {
                $message = $messageDecoder->decode($package);

                $logger->debug('Dispatch "{messageClass}" message', [
                    'packageId'    => $package->id(),
                    'traceId'      => $package->traceId(),
                    'messageClass' => \get_class($message)
                ]);

                $executors = $messagesRouter->match($message);

                if(0 === \count($executors))
                {
                    $logger->debug(
                        'There are no handlers configured for the message "{messageClass}"',
                        ['messageClass' => \get_class($message)]
                    );

                    yield $package->ack();

                    return;
                }

                /** @var \Desperado\ServiceBus\MessageExecutor\MessageExecutor $executor */
                foreach($executors as $executor)
                {
                    $context = new KernelContext($package, $endpointRouter, $logger);

                    try
                    {
                        yield $executor($message, $context);
                    }
                    catch(\Throwable $throwable)
                    {
                        $context->logContextThrowable($throwable);
                    }
                }

                yield $package->ack();
            },
            $package
        );
    }
}
