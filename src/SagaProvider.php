<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus;

use Desperado\Domain\Message\AbstractCommand;
use Desperado\ServiceBus\Application\Context\ExecutionContextInterface;
use Desperado\ServiceBus\Saga\Configuration\SagaConfiguration;
use Desperado\ServiceBus\Saga\Configuration\Exceptions\SagaConfigurationException;
use Desperado\ServiceBus\Saga\Configuration\SagaConfigurationExtractorInterface;
use Desperado\ServiceBus\Saga\Configuration\SagaListenerConfiguration;
use Desperado\ServiceBus\Saga\Exceptions;
use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;
use Desperado\ServiceBus\Saga\Metadata;
use Desperado\ServiceBus\Saga\Processor\SagaEventProcessor;
use Desperado\ServiceBus\Saga\Store\SagaStore;
use Desperado\ServiceBus\Saga\UoW;

/**
 * Sagas provider
 *
 * @api
 */
final class SagaProvider
{
    /**
     * Saga store
     *
     * @var SagaStore
     */
    private $sagaStore;

    /**
     * Configuration extractor
     *
     * @var SagaConfigurationExtractorInterface
     */
    private $configurationExtractor;

    /**
     * Configured sagas metadata
     *
     * @var Metadata\SagasMetadataCollection
     */
    private $sagasMetadataCollection;

    /**
     * Unit of Work
     *
     * @var UoW\UnitOfWork
     */
    private $unitOfWork;

    /**
     * @param SagaStore                           $sagaStore
     * @param SagaConfigurationExtractorInterface $configurationExtractor
     */
    public function __construct(
        SagaStore $sagaStore,
        SagaConfigurationExtractorInterface $configurationExtractor
    )
    {
        $this->sagaStore              = $sagaStore;
        $this->configurationExtractor = $configurationExtractor;

        $this->sagasMetadataCollection = Metadata\SagasMetadataCollection::create();
        $this->unitOfWork              = new UoW\UnitOfWork($sagaStore);
    }

    /**
     * Start new saga
     *
     * @param AbstractSagaIdentifier $id
     * @param AbstractCommand        $command
     *
     * @return AbstractSaga
     *
     * @throws Exceptions\SagaNotConfiguredException
     */
    public function start(
        AbstractSagaIdentifier $id,
        AbstractCommand $command
    ): AbstractSaga
    {
        $sagaNamespace = $id->getSagaNamespace();

        if(true === $this->sagasMetadataCollection->has($sagaNamespace))
        {
            /** @var AbstractSaga $saga */
            $saga = new $sagaNamespace(
                $id,
                $this->sagasMetadataCollection->get($sagaNamespace)
            );

            $saga->start($command);

            $this->unitOfWork->persist(UoW\ObservedSaga::new($saga));

            return $saga;
        }

        throw new Exceptions\SagaNotConfiguredException($sagaNamespace);
    }

    /**
     * Load saga
     *
     * @param AbstractSagaIdentifier $id
     *
     * @return AbstractSaga|null
     *
     * @throws \Desperado\ServiceBus\Saga\Store\Exceptions\LoadSagaFailedException
     */
    public function obtain(AbstractSagaIdentifier $id): ?AbstractSaga
    {
        $saga = $this->sagaStore->load($id);

        if(null !== $saga)
        {
            $this->unitOfWork->persist(UoW\ObservedSaga::saved($saga));
        }

        return $saga;
    }

    /**
     * Get a list of event handlers for the specified saga
     *
     * @param string $sagaNamespace
     *
     * @return Metadata\SagaListener[]
     */
    public function getSagaListeners(string $sagaNamespace): array
    {
        $metadata = $this->sagasMetadataCollection->get($sagaNamespace);

        return null !== $metadata
            ? $metadata->getListeners()
            : [];
    }

    /**
     * Saving all sagas
     * Publication of events (if context specified)/send commands
     *
     * @param ExecutionContextInterface|null $context
     *
     * @return void
     *
     * @throws \Desperado\ServiceBus\Saga\Store\Exceptions\DuplicateSagaException
     * @throws \Desperado\ServiceBus\Saga\Exceptions\CommitSagaFailedException
     */
    public function flush(ExecutionContextInterface $context = null): void
    {
        $this->unitOfWork->commit($context);
    }

    /**
     * @param string $sagaNamespace
     *
     * @return void
     *
     * @throws Exceptions\SagaClassNotFoundException
     * @throws SagaConfigurationException
     */
    public function configure(string $sagaNamespace): void
    {
        $this->assertSagaExists($sagaNamespace);

        if(false === $this->sagasMetadataCollection->has($sagaNamespace))
        {
            $this->sagasMetadataCollection->add(
                $this->configureSagaMetadata(
                    $this->configurationExtractor->extractSagaConfiguration($sagaNamespace),
                    $this->configurationExtractor->extractSagaListeners($sagaNamespace)
                )
            );
        }
    }

    /**
     * Collect saga metadata
     *
     * @param SagaConfiguration           $sagaConfiguration
     * @param SagaListenerConfiguration[] $listenersConfiguration
     *
     * @return Metadata\SagaMetadata
     */
    private function configureSagaMetadata(
        SagaConfiguration $sagaConfiguration,
        array $listenersConfiguration
    ): Metadata\SagaMetadata
    {
        $sagaMetadata = Metadata\SagaMetadata::fromBaseConfiguration($sagaConfiguration);

        foreach($listenersConfiguration as $listenerConfiguration)
        {
            $identifierField = true === $listenerConfiguration->hasCustomIdentifierProperty()
                ? $listenerConfiguration->getContainingIdentifierProperty()
                : $sagaConfiguration->getContainingIdentifierProperty();

            $eventHandler = new SagaEventProcessor(
                $sagaConfiguration->getSagaNamespace(),
                $sagaConfiguration->getIdentifierNamespace(),
                $identifierField,
                $this
            );

            $sagaMetadata->appendListener(
                Metadata\SagaListener::new(
                    $listenerConfiguration->getEventClass(),
                    \Closure::fromCallable($eventHandler)
                )
            );
        }

        return $sagaMetadata;
    }

    /**
     * Check the existence of a class of sagas
     *
     * @param string $sagaNamespace
     *
     * @return void
     *
     * @throws Exceptions\SagaClassNotFoundException
     */
    private function assertSagaExists(string $sagaNamespace): void
    {
        if(false === \class_exists($sagaNamespace))
        {
            throw new Exceptions\SagaClassNotFoundException($sagaNamespace);
        }
    }
}
