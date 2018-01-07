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

use Symfony\Component\DependencyInjection;

/**
 * Customer-configurable container parameters
 */
class BootstrapContainerConfiguration
{
    /**
     * Compiler passes give you an opportunity to manipulate other service definitions that have been registered with
     * the service container
     *
     * @var DependencyInjection\Compiler\CompilerPassInterface[]
     */
    private $compilerPassCollection;

    /**
     * Customer container configurations
     *
     * @var DependencyInjection\Extension\Extension[]
     */
    private $extensionsCollection;

    /**
     * Custom parameters to be added to the container
     *
     * @var array
     */
    private $customerParameters;

    /**
     * @param DependencyInjection\Extension\Extension[]            $extensionsCollection
     * @param DependencyInjection\Compiler\CompilerPassInterface[] $compilerPassCollection
     * @param array                                                $customerParameters
     *
     * @return self
     */
    public static function create(
        array $extensionsCollection = [],
        array $customerParameters = [],
        array $compilerPassCollection = []
    ): self
    {
        $self = new self();

        $self->compilerPassCollection = $compilerPassCollection;
        $self->extensionsCollection = $extensionsCollection;
        $self->customerParameters = $customerParameters;

        return $self;
    }

    /**
     * Get compiler pass collection
     *
     * @return DependencyInjection\Compiler\CompilerPassInterface[]
     */
    public function getCompilerPassCollection(): array
    {
        return $this->compilerPassCollection;
    }

    /**
     * Get container configurations
     *
     * @return DependencyInjection\Extension\Extension[]
     */
    public function getExtensionsCollection(): array
    {
        return $this->extensionsCollection;
    }

    /**
     * Get custom parameters to be added to the container
     *
     * @return array
     */
    public function getCustomerParameters(): array
    {
        return $this->customerParameters;
    }

    /**
     * Close constructor
     */
    private function __construct()
    {
        $this->compilerPassCollection = $this->extensionsCollection = $this->customerParameters = [];
    }
}
