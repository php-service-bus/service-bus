<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Saga\Processor;

use Desperado\Domain\Message\AbstractEvent;
use Desperado\Domain\MessageProcessor\ExecutionContextInterface;
use Desperado\ServiceBus\Saga\Identifier\AbstractSagaIdentifier;
use Desperado\ServiceBus\Saga\Processor\Exceptions\InvalidSagaIdentifierException;
use Desperado\ServiceBus\Saga\Processor\Exceptions\SagaNotFoundException;
use Desperado\ServiceBus\Saga\Processor\Guard\GuardIdentifier;
use Desperado\ServiceBus\SagaProvider;

/**
 * The saga event processor
 */
class SagaEventProcessor
{
    /**
     * Saga namespace
     *
     * @var string
     */
    private $sagaNamespace;

    /**
     * Saga identifier namespace
     *
     * @var string
     */
    private $identifierNamespace;

    /**
     * The field that contains the saga identifier
     *
     * @var string
     */
    private $containingIdentifierProperty;

    /**
     * Saga provider
     *
     * @var SagaProvider
     */
    private $sagaProvider;

    /**
     * @param string       $sagaNamespace
     * @param string       $identifierNamespace
     * @param string       $containingIdentifierProperty
     * @param SagaProvider $sagaProvider
     */
    public function __construct(
        string $sagaNamespace,
        string $identifierNamespace,
        string $containingIdentifierProperty,
        SagaProvider $sagaProvider
    )
    {
        $this->sagaNamespace = $sagaNamespace;
        $this->identifierNamespace = $identifierNamespace;
        $this->containingIdentifierProperty = $containingIdentifierProperty;
        $this->sagaProvider = $sagaProvider;
    }

    /**
     * Event handling
     *
     * @param AbstractEvent             $event
     * @param ExecutionContextInterface $context
     *
     * @return void
     */
    public function __invoke(AbstractEvent $event, ExecutionContextInterface $context): void
    {
        $identifier = $this->searchSagaIdentifier($event);
        $saga = $this->sagaProvider->obtain($identifier);

        if(null !== $saga)
        {
            $saga->transition($event);

            $this->sagaProvider->flush($context);

            return;
        }

        throw new SagaNotFoundException($identifier);
    }

    /**
     * Search saga identifier value
     *
     * @param AbstractEvent $event
     *
     * @return AbstractSagaIdentifier
     *
     * @throws InvalidSagaIdentifierException
     */
    private function searchSagaIdentifier(AbstractEvent $event): AbstractSagaIdentifier
    {
        GuardIdentifier::guardIdentifierAccessorExists($this->containingIdentifierProperty, $event);

        $identifierAccessorName = \sprintf('get%s', \ucfirst($this->containingIdentifierProperty));
        $identifierNamespace = $this->identifierNamespace;
        $identifierValue = (string) $event->$identifierAccessorName();

        GuardIdentifier::guardIdentifier($identifierValue, $event);
        GuardIdentifier::guardIdentifierClassExists($identifierNamespace);

        $identifier = new $identifierNamespace($identifierValue, $this->sagaNamespace);

        return $identifier;
    }
}
