<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Application\Bootstrap;

/**
 * Customer-configurable services
 */
final class BootstrapServicesDefinitions
{
    /**
     * The key under which the container stores the description of the transport service
     *
     * @var string
     */
    private $messageTransportKey;

    /**
     * The key under which the container stores the description of the kernel
     *
     * @var string
     */
    private $kernelKey;

    /**
     * The key under which the container stores a description of the storage service of the sagas
     *
     * @var string
     */
    private $sagaStorageKey;

    /**
     * The key under which the container stores a description of the storage service of the scheduler
     *
     * @var string
     */
    private $schedulerStorageKey;

    /**
     * The key under which the context is stored in the container for executing messages received from the bus
     *
     * @var string
     */
    private $messageBusContextKey;

    /**
     * @param string      $messageTransportKey
     * @param string      $kernelKey
     * @param string      $sagaStorageKey
     * @param string      $schedulerStorageKey
     * @param string      $messageBusContextKey
     *
     * @return self
     */
    public static function create(
        string $messageTransportKey,
        string $kernelKey,
        string $sagaStorageKey,
        string $schedulerStorageKey,
        string $messageBusContextKey
    ): self
    {
        $self = new self();

        $self->messageTransportKey = $messageTransportKey;
        $self->kernelKey = $kernelKey;
        $self->sagaStorageKey = $sagaStorageKey;
        $self->schedulerStorageKey = $schedulerStorageKey;
        $self->messageBusContextKey = $messageBusContextKey;

        return $self;
    }

    /**
     * Get key under which the container stores the description of the transport service
     *
     * @return string
     */
    public function getMessageTransportKey(): string
    {
        return $this->messageTransportKey;
    }

    /**
     * Get key under which the container stores the description of the kernel
     *
     * @return string
     */
    public function getKernelKey(): string
    {
        return $this->kernelKey;
    }

    /**
     * Get key under which the container stores a description of the storage service of the sagas
     *
     * @return string
     */
    public function getSagaStorageKey(): string
    {
        return $this->sagaStorageKey;
    }

    /**
     * Get key under which the container stores a description of the storage service of the scheduler
     *
     * @return string
     */
    public function getSchedulerStorageKey(): string
    {
        return $this->schedulerStorageKey;
    }

    /**
     * Get key under which the context is stored in the container for executing messages received from the bus
     *
     * @return string
     */
    public function getMessageBusContextKey(): string
    {
        return $this->messageBusContextKey;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
