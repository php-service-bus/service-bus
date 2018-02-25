<?php

/**
 * PHP Service Bus (CQS implementation)
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Services;

use Psr\Container\ContainerInterface;

/**
 * Search for services to be substituted as arguments to the handler
 */
final class AutowiringServiceLocator
{
    /**
     * Dependency Injection container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * Relation of the class with the service identifier
     *
     * @var array
     */
    private $servicesRelation;

    /**
     * @param ContainerInterface $container
     * @param array              $servicesRelation
     */
    public function __construct(ContainerInterface $container, array $servicesRelation)
    {
        $this->container = $container;
        $this->servicesRelation = $servicesRelation;
    }

    /**
     * Service with this class is indicated in the container?
     *
     * @param string $className
     *
     * @return bool
     */
    public function has(string $className): bool
    {
        return isset($this->servicesRelation[$className]);
    }

    /**
     * Get the service from container by its class
     *
     * @param string $className
     *
     * @return object|null
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function get(string $className)
    {
        return true === $this->has($className)
            ? $this->container->get($this->servicesRelation[$className])
            : null;
    }
}
