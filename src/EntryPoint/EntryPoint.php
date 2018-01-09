<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\EntryPoint;

use Desperado\ServiceBus\Application\AbstractKernel;
use Desperado\ServiceBus\Application\Exceptions\ApplicationContextMustBeImmutableException;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Desperado\ServiceBus\Transport\Context\OutboundMessageContext;
use Desperado\ServiceBus\Transport\IncomingMessageContainer;
use Desperado\ServiceBus\Transport\TransportInterface;

/**
 * Application entry point
 */
class EntryPoint
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
     * @var AbstractExecutionContext
     */
    private $executionContext;

    /**
     * Transmission bus
     *
     * @var TransportInterface
     */
    private $transport;

    /**
     * @param string                   $name
     * @param AbstractKernel           $kernel
     * @param AbstractExecutionContext $executionContext
     * @param TransportInterface       $transport
     */
    public function __construct(
        string $name,
        AbstractKernel $kernel,
        AbstractExecutionContext $executionContext,
        TransportInterface $transport
    )
    {
        $this->name = $name;
        $this->kernel = $kernel;
        $this->executionContext = $executionContext;
        $this->transport = $transport;
    }

    /**
     * Run application
     *
     * @param array $clients
     *
     * @return void
     */
    public function run(array $clients): void
    {
        $this->transport->listen(
            $this->name,
            function(IncomingMessageContainer $incomingMessageContainer)
            {
                $unpackedMessage = $this->transport
                    ->getMessageSerializer()
                    ->unserialize(
                        $incomingMessageContainer
                            ->getMessage()
                            ->getBody()
                    );

                $entryPointContext = EntryPointContext::create(
                    $unpackedMessage,
                    $incomingMessageContainer->getMessage()->getHeaders()
                );

                $executionContext = $this->prepareApplicationContext($incomingMessageContainer->getOutboundContext());

                return $this->kernel->handle($entryPointContext, $executionContext);
            },
            $clients
        );
    }

    /**
     * Stop application
     *
     * @return void
     */
    public function stop(): void
    {
        $this->transport->disconnect();
    }

    /**
     * Prepare execution context
     *
     * @param OutboundMessageContext $outboundMessageContext
     *
     * @return AbstractExecutionContext
     *
     * @throws ApplicationContextMustBeImmutableException
     */
    private function prepareApplicationContext(OutboundMessageContext $outboundMessageContext): AbstractExecutionContext
    {
        $originalContextKey = \spl_object_hash($this->executionContext);

        $executionContext = $this->executionContext->applyOutboundMessageContext($outboundMessageContext);

        if($originalContextKey === \spl_object_hash($executionContext))
        {
            throw new ApplicationContextMustBeImmutableException();
        }

        return $executionContext;
    }
}
