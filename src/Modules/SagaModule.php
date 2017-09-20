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

namespace Desperado\Framework\Modules;

use Desperado\CQRS\Configuration\MessageHandlerData;
use Desperado\CQRS\ExecutionContextOptions\EventListenerOptions;
use Desperado\CQRS\MessageBusBuilder;
use Desperado\EventSourcing\Saga\Configuration\SagaConfigurationExtractorInterface;
use Desperado\EventSourcing\Saga\SagaEventHandler;
use Desperado\EventSourcing\Saga\SagaStorageManagerInterface;

/**
 * Saga support module
 */
class SagaModule implements ModuleInterface
{
    /**
     * Saga storage managers
     *
     * @var SagaStorageManagerInterface[]
     */
    private $sagaStorageManagers;

    /**
     * Saga configuration extractor
     *
     * @var SagaConfigurationExtractorInterface
     */
    private $configurationExtractor;

    /**
     * @param SagaStorageManagerInterface[]       $sagaStorageManagers
     * @param SagaConfigurationExtractorInterface $configurationExtractor
     */
    public function __construct(
        array $sagaStorageManagers,
        SagaConfigurationExtractorInterface $configurationExtractor
    )
    {
        $this->sagaStorageManagers = $sagaStorageManagers;
        $this->configurationExtractor = $configurationExtractor;
    }

    /**
     * @inheritdoc
     */
    public function boot(MessageBusBuilder $messageBusBuilder): void
    {
        foreach($this->sagaStorageManagers as $storageManager)
        {
            $sagaNamespace = $storageManager->getSagaNamespace();
            $headerDeclaration = $this->configurationExtractor->extractConfigDeclaration($sagaNamespace);

            $eventListeners = $this->configurationExtractor->extractEventListeners($sagaNamespace);

            foreach($eventListeners as $eventListenerData)
            {
                $eventListenerData->containingIdentityProperty = '' !== (string) $eventListenerData->containingIdentityProperty
                    ? $eventListenerData->containingIdentityProperty
                    : $headerDeclaration->containingIdentityProperty;

                $handlerCallable = new SagaEventHandler(
                    $headerDeclaration->identityNamespace,
                    $storageManager,
                    $eventListenerData
                );

                $messageHandler = new MessageHandlerData();
                $messageHandler->messageClassNamespace = $eventListenerData->eventNamespace;
                $messageHandler->executionOptions = new EventListenerOptions();
                $messageHandler->messageHandler = \Closure::fromCallable($handlerCallable);

                $messageBusBuilder->pushMessageHandler($messageHandler);
            }
        }
    }
}