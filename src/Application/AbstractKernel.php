<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application;

use Desperado\Saga\Service\SagaService;
use Desperado\ServiceBus\EntryPoint\EntryPointContext;
use Desperado\ServiceBus\MessageBus\MessageBusBuilder;
use Desperado\ServiceBus\MessageProcessor\AbstractExecutionContext;
use Desperado\ServiceBus\Services;

/**
 * Base application kernel
 */
abstract class AbstractKernel
{
    /**
     * Message bus factory
     *
     * @var MessageBusBuilder
     */
    private $messageBusBuilder;

    /**
     * Sagas service
     *
     * @var SagaService
     */
    private $sagaService;


    final public function __construct(
        MessageBusBuilder $messageBusBuilder,
        SagaService $sagaService
    )
    {
        $this->messageBusBuilder = $messageBusBuilder;
        $this->sagaService = $sagaService;

        $this->configureSagas();
        $this->buildMessageBus();
    }

    /**
     * Get sagas list
     *
     * [
     *     0 => 'someSagaNamespace',
     *     1 => 'someSagaNamespace',
     *     ....
     * ]
     *
     *
     * @return array
     */
    protected function getSagasList(): array
    {
        return [];
    }

    /**
     * Get application services
     *
     * @return Services\ServiceInterface[]
     */
    protected function getServices(): array
    {
        return [];
    }

    /**
     * Handle message
     *
     * @param EntryPointContext        $entryPointContext
     * @param AbstractExecutionContext $executionContext
     *
     * @return void
     *
     * @throws \Throwable
     */
    final public function handle(EntryPointContext $entryPointContext, AbstractExecutionContext $executionContext): void
    {

    }

    /**
     * Get message bus builder
     *
     * @return MessageBusBuilder
     */
    final protected function getMessageBusBuilder(): MessageBusBuilder
    {
        return $this->messageBusBuilder;
    }

    private function configureSagas(): void
    {
        foreach($this->sagaService as $saga)
        {
            
        }
    }

    private function buildMessageBus(): void
    {

    }
}
