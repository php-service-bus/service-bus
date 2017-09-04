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

namespace Desperado\Framework\Application\Saga;

use Desperado\Framework\Application\Context\KernelContext;
use Desperado\Framework\Domain\Messages\EventInterface;
use Desperado\Framework\Infrastructure\EventSourcing\Annotation\SagaListener;
use Desperado\Framework\Infrastructure\StorageManager\SagaStorageManager;

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
     * Saga identity namespace
     *
     * @var string
     */
    private $identityNamespace;

    /**
     * @param string             $identityNamespace
     * @param SagaListener       $annotation
     * @param SagaStorageManager $storageManager
     */
    public function __construct(
        string $identityNamespace,
        SagaListener $annotation,
        SagaStorageManager $storageManager
    )
    {
        $this->identityNamespace = $identityNamespace;
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
            $identityNamespace = $this->identityNamespace;
            $identity = new $identityNamespace($event->{$this->annotation->containingIdentityProperty});

            if('' !== (string) $identity)
            {
                $saga = $this->storageManager->load($identity);

                if(null !== $saga)
                {
                    $saga->transition($event);
                }
            }
        }
    }
}
