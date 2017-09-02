<?php

/**
 * Command Query Responsibility Segregation, Event Sourcing implementation
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @url     https://github.com/mmasiukevich
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ConcurrencyFramework\Application\Saga;

use Desperado\ConcurrencyFramework\Application\Context\KernelContext;
use Desperado\ConcurrencyFramework\Domain\Messages\EventInterface;
use Desperado\ConcurrencyFramework\Infrastructure\EventSourcing\Annotation\SagaListener;
use Desperado\ConcurrencyFramework\Infrastructure\StorageManager\SagaStorageManager;

/**
 * Saga event handler
 */
class SagaEventHandler
{
    /**
     * Listen annotation
     *
     * @var SagaListener
     */
    private $annotation;

    /**
     * Saga storage manager
     *
     * @var SagaStorageManager
     */
    private $storageManager;

    /**
     * @param SagaListener       $annotation
     * @param SagaStorageManager $storageManager
     */
    public function __construct(SagaListener $annotation, SagaStorageManager $storageManager)
    {
        $this->annotation = $annotation;
        $this->storageManager = $storageManager;
    }

    /**
     * Invoke saga event
     *
     * @param EventInterface $event
     * @param KernelContext  $context
     *
     * @return void
     */
    public function __invoke(EventInterface $event, KernelContext $context)
    {
        if(
            true === \property_exists($event, $this->annotation->containingIdentityProperty) &&
            '' !== (string) $this->annotation->containingIdentityProperty
        )
        {
            $identityNamespace = $this->annotation->identityNamespace;
            $identity = new $identityNamespace($event->{$this->annotation->containingIdentityProperty});

            $saga = $this->storageManager->load($identity);

            if(null !== $saga)
            {
                $saga->resetUncommittedEvents();
                $saga->resetCommands();

                $saga->transition($event);

                $this->storageManager->commit($context);

                $saga->resetUncommittedEvents();
                $saga->resetCommands();

                unset($saga);
            }
        }
    }
}
