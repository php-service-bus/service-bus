<?php

/**
 * PHP Service Bus (publish-subscribe pattern implementation)
 * Supports Saga pattern and Event Sourcing
 *
 * @author  Maksim Masiukevich <desperado@minsk-info.ru>
 * @license MIT
 * @license https://opensource.org/licenses/MIT
 */

declare(strict_types = 1);

namespace Desperado\ServiceBus\Tests\DependencyInjection\ContainerBuilder\Stubs;

/**
 *
 */
final class SomeTestService
{
    /**
     * @var string
     */
    private $env;

    /**
     * @param string $env
     */
    public function __construct(?string $env = null)
    {
        $this->env = $env;
    }

    /**
     * @return string
     */
    public function env(): string
    {
        return $this->env;
    }
}
