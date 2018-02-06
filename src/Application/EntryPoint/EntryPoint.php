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
use Desperado\ServiceBus\Application\Context\Exceptions\ApplicationContextMustBeImmutableException;
use Desperado\ServiceBus\Application\Kernel\AbstractKernel;
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
     * @var ExecutionContextInterface
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
     * @param ExecutionContextInterface $executionContext
     * @param TransportInterface       $transport
     */
    public function __construct(
        string $name,
        AbstractKernel $kernel,
        ExecutionContextInterface $executionContext,
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
     * @return ExecutionContextInterface
     *
     * @throws ApplicationContextMustBeImmutableException
     */
    private function prepareApplicationContext(OutboundMessageContext $outboundMessageContext): ExecutionContextInterface
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
