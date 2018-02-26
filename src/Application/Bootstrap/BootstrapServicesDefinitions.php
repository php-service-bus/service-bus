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
     * The key under which the container contains the description of the context for executing the application layer
     *
     * @var string
     */
    private $applicationContextKey;

    /**
     * @param string $messageTransportKey
     * @param string $kernelKey
     * @param string $sagaStorageKey
     * @param string $schedulerStorageKey
     * @param string $applicationContextKey
     *
     * @return self
     */
    final public static function create(
        string $messageTransportKey,
        string $kernelKey,
        string $sagaStorageKey,
        string $schedulerStorageKey,
        string $applicationContextKey
    ): self
    {
        $self = new self();

        $self->messageTransportKey = $messageTransportKey;
        $self->kernelKey = $kernelKey;
        $self->sagaStorageKey = $sagaStorageKey;
        $self->schedulerStorageKey = $schedulerStorageKey;
        $self->applicationContextKey = $applicationContextKey;

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
     * Get key under which the container contains the description of the context for executing the application layer
     *
     * @return string
     */
    public function getApplicationContextKey(): string
    {
        return $this->applicationContextKey;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {

    }
}
